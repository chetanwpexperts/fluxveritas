<?php

namespace Tests\Feature\Import;

use App\Http\Controllers\ImportInviteController;
use App\Jobs\RunEmployeeImport;
use App\Mail\ImportInviteMail;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Designation;
use App\Models\EmployeeImport;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use App\Services\Import\ImportPresets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SmartImportTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();

        foreach (['super_admin', 'owner', 'admin', 'hr', 'team_lead', 'employee', 'viewer'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->org   = $this->organization('Acme', 'pro');
        $this->owner = $this->person('Acme Owner', 'owner@acme.test', 'owner', $this->org);
    }

    private function organization(string $name, string $plan = 'pro'): Organization
    {
        return Organization::create(['name' => $name, 'slug' => Str::slug($name) . '-' . Str::random(4), 'status' => 'active',
            'plan' => $plan, 'plan_expires_at' => $plan === 'free' ? null : now()->addMonth()]);
    }

    private function person(string $name, string $email, string $role, Organization $org, array $attrs = []): User
    {
        $user = User::factory()->create(array_merge(['name' => $name, 'email' => $email, 'organization_id' => $org->id], $attrs));
        $user->assignRole($role);

        return $user;
    }

    private function csv(array $rows, string $name = 'staff.csv'): UploadedFile
    {
        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row, ',', '"', '\\');
        }
        rewind($handle);

        return UploadedFile::fake()->createWithContent($name, stream_get_contents($handle));
    }

    private function upload(UploadedFile $file, string $preset = 'custom', ?User $as = null): EmployeeImport
    {
        $this->actingAs($as ?? $this->owner)
            ->post(route('import.employees.upload'), ['file' => $file, 'preset' => $preset])
            ->assertRedirect();

        return EmployeeImport::latest('id')->firstOrFail();
    }

    private function check(EmployeeImport $import, array $options = [], ?User $as = null, array $mapping = null)
    {
        return $this->actingAs($as ?? $this->owner)->post(route('import.employees.mapping.save', $import), array_merge([
            'mapping' => $mapping ?? $import->mapping, 'duplicates' => 'skip', 'send_invites' => 'now',
            'date_format' => 'dmy', 'skip_inactive' => 1,
        ], $options));
    }

    private function runImport(EmployeeImport $import, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->owner)->post(route('import.employees.run', $import));
    }

    private const HEADERS = ['Full Name', 'Work Email', 'Role', 'Department', 'Team', 'Designation', 'Manager Email', 'Date of Joining', 'Phone', 'Employment Type', 'Status'];

    // ── Column matching ──────────────────────────────────────────────────────

    public static function presetHeaders(): array
    {
        return [
            'Keka' => ['keka',
                ['Employee Number', 'Display Name', 'Work Email', 'Department', 'Job Title', 'Reporting To', 'Date Of Joining', 'Worker Type', 'Employment Status', 'Mobile Phone'],
                ['Display Name' => 'full_name', 'Work Email' => 'email', 'Job Title' => 'designation', 'Reporting To' => 'reporting_manager_name',
                 'Date Of Joining' => 'join_date', 'Worker Type' => 'employment_type', 'Employment Status' => 'status', 'Mobile Phone' => 'phone', 'Employee Number' => null]],
            'Zoho People' => ['zoho',
                ['Employee ID', 'First Name', 'Last Name', 'Email address', 'Department', 'Designation', 'Reporting To', 'Date of joining', 'Employee Type', 'Employee Status'],
                ['First Name' => 'first_name', 'Last Name' => 'last_name', 'Email address' => 'email', 'Reporting To' => 'reporting_manager_name',
                 'Date of joining' => 'join_date', 'Employee Type' => 'employment_type', 'Employee Status' => 'status']],
            'greytHR' => ['greythr',
                ['Employee No', 'Employee Name', 'Email', 'Date of Joining', 'Department', 'Designation', 'Location', 'Reporting Manager', 'Mobile Number', 'Status'],
                ['Employee Name' => 'full_name', 'Email' => 'email', 'Reporting Manager' => 'reporting_manager_name', 'Location' => 'work_location', 'Mobile Number' => 'phone']],
            'Darwinbox' => ['darwinbox',
                ['Employee ID', 'Full Name', 'Company Email ID', 'Department', 'Designation', 'Date of Joining', 'Direct Manager Email', 'Employee Type', 'Office Location'],
                ['Full Name' => 'full_name', 'Company Email ID' => 'email', 'Direct Manager Email' => 'reporting_manager_email', 'Office Location' => 'work_location']],
            'BambooHR' => ['bamboohr',
                ['Employee #', 'First Name', 'Last Name', 'Work Email', 'Department', 'Job Title', 'Hire Date', 'Reports To', 'Employment Status', 'Status'],
                ['Work Email' => 'email', 'Hire Date' => 'join_date', 'Reports To' => 'reporting_manager_name', 'Employment Status' => 'employment_type', 'Status' => 'status']],
        ];
    }

    #[DataProvider('presetHeaders')]
    public function test_columns_are_matched_automatically_for_each_hr_system(string $preset, array $headers, array $expected): void
    {
        $suggested = ImportPresets::suggest($headers, $preset, $this->org->id);
        $byHeader  = collect($headers)->mapWithKeys(fn ($h, $i) => [$h => $suggested[$i]['field']]);

        foreach ($expected as $header => $field) {
            $this->assertSame($field, $byHeader[$header], "{$preset}: \"{$header}\"");
        }
    }

    public function test_leave_type_columns_are_matched_to_leave_balances(): void
    {
        LeaveType::create(['organization_id' => $this->org->id, 'name' => 'Casual Leave', 'code' => 'CL', 'days_per_year' => 12, 'is_active' => true]);

        $suggested = ImportPresets::suggest(['Email', 'Casual Leave Balance'], 'custom', $this->org->id);

        $this->assertSame('leave:CL', $suggested[1]['field']);
    }

    // ── Full import ──────────────────────────────────────────────────────────

    public function test_imports_people_structure_managers_dates_and_leave(): void
    {
        LeaveType::create(['organization_id' => $this->org->id, 'name' => 'Casual Leave', 'code' => 'CL', 'days_per_year' => 12, 'is_active' => true]);

        $import = $this->upload($this->csv([
            [...self::HEADERS, 'Casual Leave Balance'],
            ['Dev One', 'dev1@acme.test', 'Employee', 'Engineering', 'Web Team', 'Software Developer', 'lead@acme.test', '15/01/2025', '98765 43210', 'Permanent', 'Active', '7.5'],
            ['Lead Person', 'lead@acme.test', 'Team Lead', 'Engineering', 'Web Team', 'Engineering Lead', '', '01-Apr-2023', '', 'Full Time', 'Active', '10'],
            ['Ops Person', 'ops@acme.test', '', '', 'Ops Crew', 'Operations Executive', 'owner@acme.test', '2024-06-30', '', 'Intern', 'Active', ''],
        ]));
        $this->assertSame('email', $import->mapping[1]);

        $this->check($import)->assertRedirect(route('import.employees.preview', $import));
        $import->refresh();
        $this->assertSame('validated', $import->status);
        $this->assertSame(3, $import->summary['preview']['stats']['create']);
        $this->assertEqualsCanonicalizing(['Engineering'], $import->summary['preview']['new']['departments']);
        $this->assertSame(1, User::where('organization_id', $this->org->id)->count(), 'Nothing saved at preview');

        $this->runImport($import)->assertRedirect(route('import.employees.show', $import));
        $import->refresh();
        $this->assertSame('completed', $import->status);
        $this->assertSame(3, $import->created_count);

        $dev  = User::where('email', 'dev1@acme.test')->firstOrFail();
        $lead = User::where('email', 'lead@acme.test')->firstOrFail();
        $ops  = User::where('email', 'ops@acme.test')->firstOrFail();

        $this->assertSame($this->org->id, $dev->organization_id);
        $this->assertTrue($dev->hasRole('employee'));
        $this->assertTrue($lead->hasRole('team_lead'));
        $this->assertSame($lead->id, $dev->reporting_manager_id, 'Manager later in the file is linked in the second pass');
        $this->assertSame($this->owner->id, $ops->reporting_manager_id, 'Existing member as manager');
        $this->assertSame('2025-01-15', $dev->join_date->toDateString());
        $this->assertSame('2023-04-01', $lead->join_date->toDateString());
        $this->assertSame('full_time', $dev->employment_type);
        $this->assertSame('intern', $ops->employment_type);
        $this->assertSame('software-developer', $dev->designation);
        $this->assertSame('Software Developer', $dev->job_title);
        $this->assertTrue(Designation::where('organization_id', $this->org->id)->where('slug', 'software-developer')->exists());

        $team = Team::where('organization_id', $this->org->id)->where('name', 'Web Team')->firstOrFail();
        $this->assertSame($team->id, $dev->team_id);
        $this->assertSame(Department::withoutGlobalScopes()->where('organization_id', $this->org->id)->where('name', 'Engineering')->value('id'), $team->department_id);
        $opsTeam = Team::where('organization_id', $this->org->id)->where('name', 'Ops Crew')->firstOrFail();
        $this->assertSame('General', Department::withoutGlobalScopes()->find($opsTeam->department_id)->name, 'Team without a department goes under General');

        $this->assertEquals(7.5, LeaveBalance::where('user_id', $dev->id)->sole()->available);
        $this->assertSame(0, LeaveBalance::where('user_id', $ops->id)->count());

        $audit = AuditLog::where('action', 'employees.imported')->sole();
        $this->assertSame($this->owner->id, $audit->user_id);
        $this->assertSame(3, $audit->new_values['created']);

        Mail::assertSent(ImportInviteMail::class, 3);
    }

    public function test_row_errors_are_reported_and_downloadable(): void
    {
        $hr = $this->person('HR Person', 'hr@acme.test', 'hr', $this->org);

        $import = $this->upload($this->csv([
            self::HEADERS,
            ['', 'noname@acme.test', '', '', '', '', '', '', '', '', ''],
            ['Bad Email', 'not-an-email', '', '', '', '', '', '', '', '', ''],
            ['First Copy', 'dup@acme.test', '', '', '', '', '', '', '', '', ''],
            ['Second Copy', 'dup@acme.test', '', '', '', '', '', '', '', '', ''],
            ['Wants Admin', 'admin@acme.test', 'Admin', '', '', '', '', '', '', '', ''],
            ['=HYPERLINK("http://x")', 'formula@acme.test', 'Wizard', '', '', '', '', '31/31/2020', '', 'Robot', ''],
            ['Former Staff', 'gone@acme.test', '', '', '', '', '', '', '', '', 'Exited'],
            ['Good Person', 'good@acme.test', '', '', '', '', '', '', '', '', ''],
        ]), 'custom', $hr);
        $this->check($import, [], $hr);

        $stats = $import->fresh()->summary['preview']['stats'];
        $this->assertSame(2, $stats['create']);  // First Copy + Good Person
        $this->assertSame(1, $stats['skip']);    // Exited
        $this->assertSame(5, $stats['error']);

        $messages = collect($import->fresh()->summary['preview']['issues'])->pluck('messages', 'line')->map(fn ($m) => implode(' | ', $m));
        $this->assertStringContainsString('Name is missing', $messages[2]);
        $this->assertStringContainsString('not valid', $messages[3]);
        $this->assertStringContainsString('Same email as line 4', $messages[5]);
        $this->assertStringContainsString("Role \"Admin\" can't be assigned", $messages[6], 'HR cannot import admins');

        $csv = $this->actingAs($hr)->get(route('import.employees.issues', $import))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv, 'Formula cells are neutralised');
        $this->assertStringContainsString('Skipped: status is ""Exited""', $csv); // quotes are doubled inside CSV
    }

    public function test_existing_people_are_skipped_or_updated(): void
    {
        $member = $this->person('Old Name', 'member@acme.test', 'employee', $this->org, ['job_title' => 'Old Title', 'phone' => '111']);
        $file   = fn () => $this->csv([self::HEADERS,
            ['New Name', 'member@acme.test', '', 'Finance', '', 'Finance Analyst', '', '', '', '', ''],
            ['Owner Renamed', 'owner@acme.test', 'Employee', '', '', '', '', '', '', '', ''],
        ]);

        $skip = $this->upload($file());
        $this->check($skip, ['duplicates' => 'skip']);
        $this->assertSame(2, $skip->fresh()->summary['preview']['stats']['skip']);

        $update = $this->upload($file());
        $this->check($update, ['duplicates' => 'update']);
        $this->runImport($update);

        $member->refresh();
        $this->assertSame('New Name', $member->name);
        $this->assertSame('Finance Analyst', $member->job_title);
        $this->assertSame('111', $member->phone, 'Blank cells never wipe existing data');
        $this->assertTrue($this->owner->fresh()->hasRole('owner'), "An owner's role is never changed by an import");
        $this->assertSame(2, $update->fresh()->updated_count);
    }

    // ── Cross-organization safety ────────────────────────────────────────────

    public function test_other_organizations_are_never_touched(): void
    {
        $rival      = $this->organization('Rival');
        $rivalOwner = $this->person('Rival Owner', 'boss@rival.test', 'owner', $rival);
        $rivalStaff = $this->person('Rival Staff', 'staff@rival.test', 'employee', $rival, ['job_title' => 'Theirs']);
        Department::withoutGlobalScopes()->create(['organization_id' => $rival->id, 'name' => 'Engineering', 'slug' => 'engineering', 'type' => 'tech', 'is_active' => true]);

        $import = $this->upload($this->csv([self::HEADERS,
            ['Hijack', 'staff@rival.test', 'Admin', 'Engineering', '', 'Spy', '', '', '', '', ''],
            ['Our Dev', 'ourdev@acme.test', '', 'Engineering', '', '', 'boss@rival.test', '', '', '', ''],
        ]));
        $this->check($import, ['duplicates' => 'update']);

        $issues = collect($import->fresh()->summary['preview']['issues'])->keyBy('line');
        $this->assertContains('This email is already used by an account outside your organization', $issues[2]['messages']);
        $this->assertStringContainsString("isn't in this file or your organization", implode(' ', $issues[3]['messages']));

        $this->runImport($import);

        $this->assertSame($rival->id, $rivalStaff->fresh()->organization_id);
        $this->assertSame('Theirs', $rivalStaff->fresh()->job_title);
        $this->assertFalse($rivalStaff->fresh()->hasRole('admin'));
        $ourDev = User::where('email', 'ourdev@acme.test')->firstOrFail();
        $this->assertNull($ourDev->reporting_manager_id, 'Manager from another org is never linked');
        $this->assertNotSame(
            Department::withoutGlobalScopes()->where('organization_id', $rival->id)->value('id'),
            $ourDev->department_id,
            'Same-named department from another org is never reused'
        );
        $this->assertSame($this->org->id, Department::withoutGlobalScopes()->find($ourDev->department_id)->organization_id);

        // Another organization's owner can't see, run or download this import
        foreach (['show' => 'get', 'preview' => 'get', 'issues' => 'get', 'progress' => 'get', 'run' => 'post', 'invites' => 'post'] as $route => $method) {
            $this->actingAs($rivalOwner)->{$method}(route("import.employees.{$route}", $import))->assertNotFound();
        }
    }

    public function test_only_owner_admin_and_hr_can_import(): void
    {
        $employee = $this->person('Staff', 'staff@acme.test', 'employee', $this->org);
        $lead     = $this->person('Lead', 'lead2@acme.test', 'team_lead', $this->org);
        $hr       = $this->person('HR', 'hr2@acme.test', 'hr', $this->org);

        $this->actingAs($employee)->get(route('import.employees'))->assertForbidden();
        $this->actingAs($lead)->post(route('import.employees.upload'), ['file' => $this->csv([self::HEADERS]), 'preset' => 'custom'])->assertForbidden();
        $this->actingAs($hr)->get(route('import.employees'))->assertOk();
    }

    public function test_free_plan_limit_blocks_the_import(): void
    {
        $free  = $this->organization('Tiny', 'free');
        $owner = $this->person('Tiny Owner', 'owner@tiny.test', 'owner', $free);
        User::factory()->count(7)->create(['organization_id' => $free->id]);

        $rows = [self::HEADERS];
        foreach (range(1, 5) as $i) {
            $rows[] = ["Person {$i}", "p{$i}@tiny.test", '', '', '', '', '', '', '', '', ''];
        }
        $import = $this->upload($this->csv($rows), 'custom', $owner);
        $this->check($import, [], $owner);

        $this->actingAs($owner)->get(route('import.employees.preview', $import))->assertOk()->assertSee('Free plan includes up to 10 people');
        $this->runImport($import, $owner)->assertSessionHasErrors('import');
        $this->assertSame(8, User::where('organization_id', $free->id)->count());
    }

    // ── Invitations ──────────────────────────────────────────────────────────

    public function test_invites_later_and_single_use_set_password_link(): void
    {
        $import = $this->upload($this->csv([self::HEADERS, ['New Joiner', 'joiner@acme.test', '', '', '', '', '', '', '', '', '']]));
        $this->check($import, ['send_invites' => 'later']);
        $this->runImport($import);
        Mail::assertNothingSent();

        $this->actingAs($this->owner)->post(route('import.employees.invites', $import))->assertSessionHas('success');
        $joiner = User::where('email', 'joiner@acme.test')->firstOrFail();
        Mail::assertSent(ImportInviteMail::class, fn ($mail) => $mail->hasTo('joiner@acme.test'));

        auth()->logout();
        $url = ImportInviteController::urlFor($joiner);
        $this->get($url)->assertOk()->assertSee('Welcome, New Joiner');
        $this->post($url, ['password' => 'Str0ng!Passw0rd', 'password_confirmation' => 'Str0ng!Passw0rd'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($joiner);

        auth()->logout();
        $this->get($url)->assertForbidden(); // used once
    }

    public function test_tampered_or_expired_invite_link_is_refused(): void
    {
        $user = $this->person('New Joiner', 'joiner@acme.test', 'employee', $this->org);
        $url  = ImportInviteController::urlFor($user);

        $this->get(str_replace('fp=', 'fp=x', $url))->assertForbidden();
        $this->travel(ImportInviteController::VALID_DAYS + 1)->days();
        $this->get($url)->assertForbidden();
    }

    // ── Files ────────────────────────────────────────────────────────────────

    public function test_xlsx_with_excel_dates(): void
    {
        $sheet = new Spreadsheet();
        $sheet->getActiveSheet()->fromArray([
            ['Name', 'Email', 'Hire Date'],
            ['Excel Person', 'excel@acme.test', 45306], // 15 Jan 2024 as an Excel serial
        ]);
        $path = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
        (new Xlsx($sheet))->save($path);

        $import = $this->upload(new UploadedFile($path, 'people.xlsx', null, null, true));
        $this->check($import);
        $this->runImport($import);

        $this->assertSame('2024-01-15', User::where('email', 'excel@acme.test')->firstOrFail()->join_date->toDateString());
        @unlink($path);
    }

    public function test_large_files_go_to_the_queue(): void
    {
        Queue::fake();
        config(['imports.queue_threshold' => 2]);

        $import = $this->upload($this->csv([self::HEADERS,
            ['A', 'a@acme.test', '', '', '', '', '', '', '', '', ''],
            ['B', 'b@acme.test', '', '', '', '', '', '', '', '', ''],
            ['C', 'c@acme.test', '', '', '', '', '', '', '', '', ''],
        ]));
        $this->check($import);
        $this->runImport($import);

        Queue::assertPushed(RunEmployeeImport::class);
        $this->assertSame('queued', $import->fresh()->status);
        $this->actingAs($this->owner)->getJson(route('import.employees.progress', $import))
            ->assertOk()->assertJson(['status' => 'queued', 'finished' => false, 'total' => 3]);
    }

    public function test_five_thousand_row_file(): void
    {
        $rows = [self::HEADERS];
        for ($i = 1; $i <= 5000; $i++) {
            $dept    = ['Engineering', 'Sales', 'Operations', 'Finance'][$i % 4];
            $manager = $i > 10 ? 'person' . (($i % 10) + 1) . '@acme.test' : '';
            $rows[]  = ["Person {$i}", "person{$i}@acme.test", '', $dept, "{$dept} Team " . ($i % 5), 'Associate', $manager, '01/07/2024', '', 'Full Time', 'Active'];
        }

        $import = $this->upload($this->csv($rows, 'big.csv'));
        $this->check($import)->assertRedirect(route('import.employees.preview', $import));
        $this->assertSame(5000, $import->fresh()->summary['preview']['stats']['create']);

        $this->runImport($import); // queue is "sync" in tests, so the job runs here

        $import->refresh();
        $this->assertSame('completed', $import->status);
        $this->assertSame(5000, $import->created_count);
        $this->assertSame(5000, $import->processed_rows);
        $this->assertSame(100, $import->progressPercent());
        $this->assertSame(5001, User::where('organization_id', $this->org->id)->count());
        $this->assertSame(4990, $import->summary['managers_linked']);
        $this->assertSame(4, Department::withoutGlobalScopes()->where('organization_id', $this->org->id)->count());
        $this->assertSame(20, Team::where('organization_id', $this->org->id)->count());
    }
}
