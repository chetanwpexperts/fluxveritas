<?php

namespace App\Services\Import;

use App\Models\Department;
use App\Models\Designation;
use App\Models\EmployeeImport;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Spatie\Permission\Models\Role;

/**
 * Turns an uploaded file + column mapping into clean rows and a list of issues.
 * Used for the preview and again by the import itself, so the import can never
 * do something the preview didn't show.
 *
 * Everything is checked against the importer's organization only.
 */
class EmployeeImportValidator
{
    private const INACTIVE = ['exited', 'inactive', 'terminated', 'resigned', 'relieved', 'separated', 'left', 'absconded', 'deceased'];

    private const ROLE_WORDS = [
        'employee' => 'employee', 'staff' => 'employee', 'member' => 'employee', 'team member' => 'employee',
        'team lead' => 'team_lead', 'team leader' => 'team_lead', 'lead' => 'team_lead', 'team_lead' => 'team_lead',
        'hr' => 'hr', 'human resources' => 'hr', 'admin' => 'admin', 'administrator' => 'admin',
        'viewer' => 'viewer', 'read only' => 'viewer', 'manager' => 'manager',
    ];

    private const EMPLOYMENT = [
        'full time' => 'full_time', 'fulltime' => 'full_time', 'full_time' => 'full_time', 'permanent' => 'full_time', 'regular' => 'full_time', 'employee' => 'full_time',
        'part time' => 'part_time', 'part_time' => 'part_time', 'parttime' => 'part_time',
        'contract' => 'contract', 'contractor' => 'contract', 'consultant' => 'contract', 'freelancer' => 'contract', 'temporary' => 'contract',
        'intern' => 'intern', 'internship' => 'intern', 'trainee' => 'intern', 'apprentice' => 'intern',
        'probation' => 'probation', 'probationary' => 'probation',
    ];

    /**
     * @return array{
     *   rows: array<int, array>, issues: array<int, array>,
     *   stats: array{total:int, create:int, update:int, skip:int, error:int, warning:int},
     *   new: array{departments: string[], teams: string[], designations: string[]}
     * }
     */
    public function validate(EmployeeImport $import, User $importer): array
    {
        $file    = app(ImportFileReader::class)->read(Storage::disk('local')->path($import->file_path), pathinfo($import->file_path, PATHINFO_EXTENSION));
        $mapping = $this->fieldColumns($import->mapping ?? []);
        $orgId   = $import->organization_id;
        $options = $import->options ?? [];
        $allowed = $this->allowedRoles($importer);

        // Pull only what's needed: accounts matching the file's emails, and this org's people for manager lookups
        $emails   = collect($file['rows'])->map(fn ($r) => mb_strtolower(trim($r['cells'][$mapping['email'] ?? -1] ?? '')))->filter()->unique();
        $existing = $emails->chunk(1000)->flatMap(fn ($chunk) => User::whereIn('email', $chunk->all())->get(['id', 'email', 'organization_id', 'name']))
            ->keyBy(fn ($u) => mb_strtolower($u->email));
        $orgPeople = User::where('organization_id', $orgId)->get(['id', 'email', 'name']);

        $rows = $issues = [];
        $seen = [];
        $stats = ['total' => count($file['rows']), 'create' => 0, 'update' => 0, 'skip' => 0, 'error' => 0, 'warning' => 0];

        foreach ($file['rows'] as $raw) {
            $get      = fn (string $field) => isset($mapping[$field]) ? trim((string) ($raw['cells'][$mapping[$field]] ?? '')) : '';
            $errors   = $warnings = [];
            $email    = mb_strtolower($get('email'));
            $name     = $get('full_name') ?: trim($get('first_name') . ' ' . $get('last_name'));

            if ($email === '') {
                $errors[] = 'Email is missing';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Email \"{$email}\" is not valid";
            }
            if ($name === '') {
                $errors[] = 'Name is missing';
            } elseif (mb_strlen($name) > 255) {
                $errors[] = 'Name is longer than 255 characters';
            }

            // Exited employees
            $status = mb_strtolower($get('status'));
            if ($status !== '' && ($options['skip_inactive'] ?? true) && Str::contains($status, self::INACTIVE)) {
                $this->issue($issues, $stats, $raw['line'], $email, $name, 'skipped', ["Skipped: status is \"{$get('status')}\""]);
                continue;
            }

            // Duplicates
            $action = 'create';
            if ($email !== '' && isset($seen[$email])) {
                $errors[] = "Same email as line {$seen[$email]}";
            } elseif ($email !== '' && ($account = $existing->get($email))) {
                if ((int) $account->organization_id !== $orgId) {
                    $errors[] = 'This email is already used by an account outside your organization';
                } elseif (($options['duplicates'] ?? 'skip') === 'update') {
                    $action = 'update';
                } else {
                    $this->issue($issues, $stats, $raw['line'], $email, $name, 'skipped', ['Skipped: already in your organization']);
                    $seen[$email] = $raw['line'];
                    continue;
                }
            }

            // Role
            $role = null;
            if (($roleText = $get('role')) !== '') {
                $role = self::ROLE_WORDS[mb_strtolower($roleText)] ?? null;
                if (!$role || !in_array($role, $allowed, true)) {
                    $errors[] = "Role \"{$roleText}\" can't be assigned (use: " . implode(', ', $allowed) . ')';
                }
            }

            // Employment type
            $employment = null;
            if (($typeText = $get('employment_type')) !== '') {
                $employment = self::EMPLOYMENT[mb_strtolower(str_replace('-', ' ', $typeText))] ?? null;
                if (!$employment) {
                    $warnings[] = "Unknown employment type \"{$typeText}\" — saved as full time";
                    $employment = 'full_time';
                }
            }

            // Join date
            $joinDate = null;
            if (($dateText = $get('join_date')) !== '') {
                $joinDate = $this->parseDate($dateText, $options['date_format'] ?? 'dmy');
                if (!$joinDate) {
                    $warnings[] = "Couldn't read the joining date \"{$dateText}\" — left blank";
                }
            }

            // Leave balances
            $leave = [];
            foreach ($mapping as $field => $col) {
                if (!str_starts_with($field, 'leave:') || ($value = $get($field)) === '') {
                    continue;
                }
                if (!is_numeric($value) || $value < 0 || $value > 365) {
                    $errors[] = 'Leave balance for ' . substr($field, 6) . " must be a number from 0 to 365 (got \"{$value}\")";
                    continue;
                }
                $leave[substr($field, 6)] = (float) $value;
            }

            if ($email !== '') {
                $seen[$email] = $raw['line'];
            }

            if ($errors) {
                $this->issue($issues, $stats, $raw['line'], $email, $name, 'error', array_merge($errors, $warnings));
                continue;
            }

            $rows[] = [
                'line'            => $raw['line'],
                'action'          => $action,
                'name'            => $name,
                'email'           => $email,
                'role'            => $role,
                'department'      => Str::limit($get('department'), 100, ''),
                'team'            => Str::limit($get('team'), 100, ''),
                'designation'     => Str::limit($get('designation'), 150, ''),
                'manager_email'   => mb_strtolower($get('reporting_manager_email')),
                'manager_name'    => $get('reporting_manager_name'),
                'join_date'       => $joinDate,
                'phone'           => Str::limit(preg_replace('/[^0-9+\-\s()]/', '', $get('phone')), 20, ''),
                'employment_type' => $employment,
                'work_location'   => Str::limit($get('work_location'), 100, ''),
                'leave'           => $leave,
                'warnings'        => $warnings,
            ];
            $stats[$action]++;
        }

        $this->checkManagers($rows, $orgPeople);

        foreach ($rows as $row) {
            if ($row['warnings']) {
                $this->issue($issues, $stats, $row['line'], $row['email'], $row['name'], 'warning', $row['warnings']);
            }
        }

        return ['rows' => $rows, 'issues' => $issues, 'stats' => $stats, 'new' => $this->newStructures($rows, $orgId)];
    }

    /** Roles this person may give out through an import (only roles that exist). */
    public function allowedRoles(User $importer): array
    {
        $roles = $importer->hasAnyRole(['owner', 'admin', 'super_admin'])
            ? ['employee', 'team_lead', 'hr', 'viewer', 'admin', 'manager']
            : ['employee', 'team_lead', 'viewer'];

        return Role::whereIn('name', $roles)->where('guard_name', 'web')->pluck('name')
            ->sortBy(fn ($r) => array_search($r, $roles, true))->values()->all();
    }

    /** Warns when a manager can't be found in the file or the organization. */
    private function checkManagers(array &$rows, $orgPeople): void
    {
        $inFileEmails = array_flip(array_column($rows, 'email'));
        $orgEmails    = $orgPeople->pluck('id', 'email')->mapWithKeys(fn ($id, $e) => [mb_strtolower($e) => $id]);
        $names        = collect($rows)->pluck('name')->merge($orgPeople->pluck('name'))->map(fn ($n) => mb_strtolower($n))->countBy();

        foreach ($rows as &$row) {
            if ($row['manager_email'] !== '') {
                if ($row['manager_email'] === $row['email']) {
                    $row['warnings'][] = 'Reporting manager is the same person — left blank';
                    $row['manager_email'] = '';
                } elseif (!isset($inFileEmails[$row['manager_email']]) && !isset($orgEmails[$row['manager_email']])) {
                    $row['warnings'][] = "Manager {$row['manager_email']} isn't in this file or your organization — left blank";
                    $row['manager_email'] = '';
                }
            } elseif ($row['manager_name'] !== '') {
                $count = $names[mb_strtolower($row['manager_name'])] ?? 0;
                if ($count !== 1) {
                    $row['warnings'][] = $count === 0
                        ? "Manager \"{$row['manager_name']}\" not found — left blank"
                        : "Several people are called \"{$row['manager_name']}\" — manager left blank (map a manager email column instead)";
                    $row['manager_name'] = '';
                }
            }
        }
    }

    /** @return array{departments: string[], teams: string[], designations: string[]} names that will be created */
    private function newStructures(array $rows, int $orgId): array
    {
        $existingDepts  = Department::withoutGlobalScopes()->where('organization_id', $orgId)->pluck('name')->map(fn ($n) => mb_strtolower($n))->flip();
        $existingTeams  = Team::where('organization_id', $orgId)->pluck('name')->map(fn ($n) => mb_strtolower($n))->flip();
        $existingTitles = Designation::where('organization_id', $orgId)->pluck('title')->map(fn ($n) => mb_strtolower($n))->flip();

        $collect = fn (string $key, $existing) => collect($rows)->pluck($key)->filter()
            ->unique(fn ($n) => mb_strtolower($n))
            ->reject(fn ($n) => $existing->has(mb_strtolower($n)))->values()->all();

        return [
            'departments'  => $collect('department', $existingDepts),
            'teams'        => $collect('team', $existingTeams),
            'designations' => $collect('designation', $existingTitles),
        ];
    }

    /** @param array<int, ?string> $mapping column index => field */
    private function fieldColumns(array $mapping): array
    {
        $columns = [];
        foreach ($mapping as $col => $field) {
            if ($field && !isset($columns[$field])) {
                $columns[$field] = (int) $col;
            }
        }

        return $columns;
    }

    public function parseDate(string $value, string $order = 'dmy'): ?string
    {
        $value = trim($value);

        // Excel serial date (e.g. 45123)
        if (is_numeric($value) && (float) $value > 20000 && (float) $value < 80000) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
        }

        $dayFirst   = ['d/m/Y', 'd-m-Y', 'd.m.Y', 'd/m/y', 'd-m-y'];
        $monthFirst = ['m/d/Y', 'm-d-Y', 'm/d/y'];
        $formats    = array_merge(['Y-m-d', 'Y/m/d', 'Y-m-d H:i:s'], $order === 'mdy' ? $monthFirst : $dayFirst,
            ['d-M-Y', 'd M Y', 'd-M-y', 'j M Y', 'j F Y', 'M d, Y', 'F j, Y', 'd F Y']);

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat('!' . $format, $value);
            $errors = \DateTime::getLastErrors();
            if ($date && (!$errors || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                $year = (int) $date->format('Y');
                return $year >= 1950 && $year <= (int) now()->format('Y') + 2 ? $date->format('Y-m-d') : null;
            }
        }

        return null;
    }

    private function issue(array &$issues, array &$stats, int $line, string $email, string $name, string $level, array $messages): void
    {
        $issues[] = ['line' => $line, 'email' => $email, 'name' => $name, 'level' => $level, 'messages' => $messages];
        $stats[$level === 'skipped' ? 'skip' : $level]++;
    }
}
