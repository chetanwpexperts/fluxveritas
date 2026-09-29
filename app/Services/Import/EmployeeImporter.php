<?php

namespace App\Services\Import;

use App\Exceptions\WorkflowException;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Designation;
use App\Models\EmployeeImport;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Team;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Writes a validated import: people, departments, teams, designations, leave
 * balances, then reporting managers in a second pass. Works in chunks and
 * records progress on the EmployeeImport row. Everything is created in, and
 * looked up within, the import's organization only.
 */
class EmployeeImporter
{
    private const CHUNK = 250;

    private array $departments = [];
    private array $teams       = [];
    private array $titles      = [];
    private array $leaveTypes  = [];
    private int $orgId;

    public function __construct(
        private EmployeeImportValidator $validator,
        private ImportIssueReport $report,
    ) {}

    public function run(EmployeeImport $import): EmployeeImport
    {
        $importer = $import->importer;
        if (!$importer || (int) $importer->organization_id !== (int) $import->organization_id
            || !$importer->hasAnyRole(['owner', 'admin', 'hr', 'super_admin'])) {
            throw new WorkflowException('The person who started this import can no longer import employees.');
        }

        $this->orgId = $import->organization_id;
        $result      = $this->validator->validate($import, $importer);
        $rows        = $result['rows'];

        $newPeople = collect($rows)->where('action', 'create')->count();
        if ($limit = app(BillingService::class)->seatLimitError($import->organization, $newPeople)) {
            throw new WorkflowException($limit);
        }

        $import->update([
            'status' => 'running', 'started_at' => now(), 'total_rows' => $result['stats']['total'],
            'processed_rows' => $result['stats']['total'] - count($rows),
        ]);

        $this->loadStructures();
        $issues  = $result['issues'];
        $userIds = [];
        $created = $updated = 0;
        $reported = 0;

        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            DB::transaction(function () use ($chunk, $importer, &$userIds, &$created, &$updated, &$issues, &$reported) {
                foreach ($chunk as $row) {
                    try {
                        $user = $row['action'] === 'update'
                            ? $this->updatePerson($row)
                            : $this->createPerson($row, $importer);
                        $row['action'] === 'update' ? $updated++ : $created++;
                        $userIds[$row['email']] = ['id' => $user->id, 'new' => $row['action'] === 'create'];
                        $this->applyLeave($user, $row['leave']);
                    } catch (\Throwable $e) {
                        if ($reported++ < 5) { // log a few, not thousands, when a whole file fails the same way
                            report($e);
                        }
                        $issues[] = ['line' => $row['line'], 'email' => $row['email'], 'name' => $row['name'], 'level' => 'error',
                            'messages' => ['Could not be saved — please check this row and import it again']];
                    }
                }
            });
            $import->increment('processed_rows', count($chunk));
        }

        $managersLinked = $this->linkManagers($rows, $userIds);

        $errors  = collect($issues)->where('level', 'error')->count();
        $skipped = collect($issues)->where('level', 'skipped')->count();
        $summary = [
            'stats'           => $result['stats'],
            'new'             => $result['new'],
            'managers_linked' => $managersLinked,
            'created_user_ids'=> collect($userIds)->where('new', true)->pluck('id')->values()->all(),
            'warnings'        => collect($issues)->where('level', 'warning')->count(),
        ];

        $import->update([
            'status'         => 'completed',
            'finished_at'    => now(),
            'processed_rows' => $result['stats']['total'],
            'created_count'  => $created,
            'updated_count'  => $updated,
            'skipped_count'  => $skipped,
            'error_count'    => $errors,
            'summary'        => $summary,
            'errors_path'    => $this->report->write($import, $issues),
        ]);

        AuditLog::create([
            'organization_id' => $import->organization_id,
            'user_id'         => $importer->id,
            'action'          => 'employees.imported',
            'entity_type'     => 'employee_import',
            'entity_id'       => $import->id,
            'new_values'      => [
                'file' => $import->original_name, 'preset' => $import->preset, 'rows' => $result['stats']['total'],
                'created' => $created, 'updated' => $updated, 'skipped' => $skipped, 'errors' => $errors,
                'duplicates' => $import->option('duplicates'), 'send_invites' => $import->option('send_invites'),
                'new_departments' => count($result['new']['departments']), 'new_teams' => count($result['new']['teams']),
            ],
        ]);

        return $import->fresh();
    }

    // ── People ───────────────────────────────────────────────────────────────

    private function createPerson(array $row, User $importer): User
    {
        [$deptId, $teamId] = $this->placement($row);
        $designation       = $this->designation($row['designation']);
        $role              = $row['role'] ?: 'employee';

        // Blank optional values are left out so column defaults apply (work_location is NOT NULL)
        $user = User::create(array_filter([
            'name'              => $row['name'],
            'email'             => $row['email'],
            'password'          => Str::random(40),   // unusable until they set their own via the invite
            'organization_id'   => $this->orgId,
            'department_id'     => $deptId,
            'department'        => $row['department'] ?: null,
            'team_id'           => $teamId,
            'role'              => $role,
            'designation'       => $designation?->slug,
            'job_title'         => $designation?->title,
            'phone'             => $row['phone'] ?: null,
            'employment_type'   => $row['employment_type'] ?: 'full_time',
            'join_date'         => $row['join_date'],
            'work_location'     => $row['work_location'] ?: null,
            'is_active'         => true,
            'onboarding_status' => 'active',
            'onboarding_type'   => 'team_member',
            'approved_at'       => now(),
            'approved_by'       => $importer->id,
        ], fn ($v) => $v !== null));
        $user->syncRoles([$role]);

        return $user;
    }

    /** Fills in mapped values; never blanks existing data or changes an owner's/admin's role. */
    private function updatePerson(array $row): User
    {
        $user = User::where('organization_id', $this->orgId)->where('email', $row['email'])->firstOrFail();
        [$deptId, $teamId] = $this->placement($row);
        $designation       = $this->designation($row['designation']);

        $user->update(array_filter([
            'name'            => $row['name'],
            'department_id'   => $deptId,
            'department'      => $row['department'] ?: null,
            'team_id'         => $teamId,
            'designation'     => $designation?->slug,
            'job_title'       => $designation?->title,
            'phone'           => $row['phone'] ?: null,
            'employment_type' => $row['employment_type'],
            'join_date'       => $row['join_date'],
            'work_location'   => $row['work_location'] ?: null,
        ], fn ($v) => $v !== null));

        if ($row['role'] && !$user->hasAnyRole(['owner', 'super_admin', 'admin'])) {
            $user->update(['role' => $row['role']]);
            $user->syncRoles([$row['role']]);
        }

        return $user;
    }

    private function applyLeave(User $user, array $balances): void
    {
        foreach ($balances as $code => $available) {
            $type = $this->leaveTypes[$code] ?? null;
            if (!$type) {
                continue;
            }
            $balance = LeaveBalance::firstOrNew(['user_id' => $user->id, 'leave_type_id' => $type->id, 'year' => now()->year]);
            $balance->organization_id = $this->orgId;
            $balance->used            ??= 0;
            $balance->pending         ??= 0;
            $balance->carried_forward ??= 0;
            // Set so that available = imported balance
            $balance->allocated = $available + (float) $balance->used + (float) $balance->pending - (float) $balance->carried_forward;
            $balance->save();
        }
    }

    /** Second pass: reporting managers by email (or unique name), within the organization. */
    private function linkManagers(array $rows, array $userIds): int
    {
        $orgPeople = User::where('organization_id', $this->orgId)->get(['id', 'email', 'name']);
        $byEmail   = $orgPeople->mapWithKeys(fn ($u) => [mb_strtolower($u->email) => $u->id]);
        $byName    = $orgPeople->groupBy(fn ($u) => mb_strtolower($u->name))->filter(fn ($g) => $g->count() === 1)->map(fn ($g) => $g->first()->id);
        $linked    = 0;

        foreach ($rows as $row) {
            $userId = $userIds[$row['email']]['id'] ?? null;
            $manager = $row['manager_email'] !== ''
                ? ($byEmail[$row['manager_email']] ?? null)
                : ($row['manager_name'] !== '' ? ($byName[mb_strtolower($row['manager_name'])] ?? null) : null);

            if ($userId && $manager && $manager !== $userId) {
                User::whereKey($userId)->where('organization_id', $this->orgId)->update(['reporting_manager_id' => $manager]);
                $linked++;
            }
        }

        return $linked;
    }

    // ── Departments, teams, designations ─────────────────────────────────────

    private function loadStructures(): void
    {
        $this->departments = Department::withoutGlobalScopes()->where('organization_id', $this->orgId)->get()->keyBy(fn ($d) => mb_strtolower($d->name))->all();
        $this->teams       = Team::where('organization_id', $this->orgId)->get()->keyBy(fn ($t) => mb_strtolower($t->name))->all();
        $this->titles      = Designation::where('organization_id', $this->orgId)->get()->keyBy(fn ($d) => mb_strtolower($d->title))->all();
        $this->leaveTypes  = LeaveType::where('organization_id', $this->orgId)->where('is_active', true)->get()->keyBy('code')->all();
    }

    /** @return array{0: ?int, 1: ?int} department id, team id */
    private function placement(array $row): array
    {
        $dept = $row['department'] !== '' ? $this->department($row['department']) : null;

        if ($row['team'] === '') {
            return [$dept?->id, null];
        }

        $key = mb_strtolower($row['team']);
        if (!isset($this->teams[$key])) {
            // Teams belong to a department; fall back to "General" when the row has none
            $home = $dept ?? $this->department('General');
            $this->teams[$key] = Team::create([
                'organization_id' => $this->orgId, 'department_id' => $home->id, 'name' => $row['team'],
                'slug' => $this->uniqueSlug(Team::class, $row['team']), 'is_active' => true,
            ]);
        }
        $team = $this->teams[$key];

        return [$dept?->id ?? $team->department_id, $team->id];
    }

    private function department(string $name): Department
    {
        $key = mb_strtolower($name);

        return $this->departments[$key] ??= Department::create([
            'organization_id' => $this->orgId, 'name' => $name, 'slug' => $this->uniqueSlug(Department::class, $name),
            'type' => 'other', 'is_active' => true,
        ]);
    }

    private function designation(string $title): ?Designation
    {
        if ($title === '') {
            return null;
        }
        $key = mb_strtolower($title);

        return $this->titles[$key] ??= Designation::create([
            'organization_id' => $this->orgId, 'title' => $title, 'slug' => $this->uniqueSlug(Designation::class, $title),
            'category' => $this->category($title), 'is_active' => true, 'is_template' => false,
        ]);
    }

    private function category(string $title): string
    {
        $t = mb_strtolower($title);

        return match (true) {
            Str::contains($t, ['develop', 'engineer', 'devops', 'architect', 'programmer']) => 'engineering',
            Str::contains($t, ['design', 'ux', 'ui ']) => 'design',
            Str::contains($t, ['qa', 'test', 'quality']) => 'qa',
            Str::contains($t, ['hr', 'human resource', 'recruit', 'talent']) => 'hr',
            Str::contains($t, ['market', 'sales', 'growth', 'account']) => 'marketing',
            Str::contains($t, ['product']) => 'product',
            default => 'general',
        };
    }

    /** @param class-string $model */
    private function uniqueSlug(string $model, string $name): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $i    = 2;
        while ($model::withoutGlobalScopes()->where('organization_id', $this->orgId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
