<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Organization;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class EmployeeImportController extends Controller
{
    private function guard(): void
    {
        abort_if(
            !auth()->user()->hasAnyRole(['owner', 'admin', 'super_admin']),
            403,
            'Only owners and admins can import employees.'
        );
    }

    public static array $columns = [
        'name',
        'email',
        'role',
        'designation',
        'department',
        'team',
        'reporting_manager_email',
        'phone',
        'employment_type',
    ];

    private array $validRoles = ['employee', 'team_lead', 'manager', 'hr', 'admin'];

    public function index()
    {
        $this->guard();
        return view('import.employees');
    }

    public function template(Request $request)
    {
        $this->guard();
        $format = $request->get('format', 'csv');

        $spreadsheet = new Spreadsheet();

        // ── Sheet 1: Employees ────────────────────────────────────────────────
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Employees');

        $col = 'A';
        foreach (self::$columns as $heading) {
            $sheet->setCellValue($col . '1', $heading);
            $col++;
        }

        $examples = [
            ['Alex Kumar',   'alex@company.com',  'employee',  'Frontend Developer',  'Engineering', 'Backend Team', 'priya@company.com', '9876543210', 'full_time'],
            ['Priya Patel',  'priya@company.com', 'team_lead', 'Lead Developer',      'Engineering', 'Backend Team', 'rahul@company.com', '9876500000', 'full_time'],
            ['Rahul Sharma', 'rahul@company.com', 'manager',   'Engineering Manager', 'Engineering', '',             '',                  '9811111111', 'full_time'],
        ];

        $rowNum = 2;
        foreach ($examples as $row) {
            $col = 'A';
            foreach ($row as $val) {
                $sheet->setCellValue($col . $rowNum, $val);
                $col++;
            }
            $rowNum++;
        }

        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        foreach (range('A', 'I') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        // ── Sheet 2: Instructions (XLSX only) ────────────────────────────────
        if ($format === 'xlsx') {
            $info = $spreadsheet->createSheet();
            $info->setTitle('Instructions');

            $lines = [
                ['How to fill this template'],
                [''],
                ['Fill the "Employees" sheet. One row per person. Do not rename the column headers.'],
                [''],
                ['ROLE column — use ONE of these exact values:'],
                ['  employee   - Regular team member. Sees only their own work.'],
                ['  team_lead  - Leads one team. Manages their team members.'],
                ['  manager    - Manages multiple teams and team leads.'],
                ['  hr         - People operations. Manages employees, leaves, documents.'],
                ['  admin      - Full organization control (use sparingly).'],
                [''],
                ['DEPARTMENT column — type the department name (e.g. Engineering).'],
                ['  If it does not exist yet, it will be created automatically.'],
                [''],
                ['TEAM column — type the team name (e.g. Backend Team). Optional.'],
                ['  Created automatically if new. Leave blank if not in a team.'],
                [''],
                ['REPORTING_MANAGER_EMAIL — the email of this person\'s manager.'],
                ['  Must match another email in this file or an existing user. Optional.'],
                [''],
                ['EMPLOYMENT_TYPE — full_time, part_time, contract, or intern.'],
                [''],
                ['EMAIL — must be unique. Each person gets a link to set their password.'],
            ];

            $r = 1;
            foreach ($lines as $line) {
                $info->setCellValue('A' . $r, $line[0]);
                $r++;
            }

            $info->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            foreach ([5, 12, 16, 19, 22] as $boldRow) {
                $info->getStyle('A' . $boldRow)->getFont()->setBold(true);
            }
            $info->getColumnDimension('A')->setWidth(80);

            $spreadsheet->setActiveSheetIndex(0);
        }

        $filename = 'employee_import_template.' . ($format === 'xlsx' ? 'xlsx' : 'csv');

        if ($format === 'xlsx') {
            $writer      = new XlsxWriter($spreadsheet);
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } else {
            $writer      = new CsvWriter($spreadsheet);
            $contentType = 'text/csv';
        }

        return response()->streamDownload(function () use ($writer, $format) {
            if ($format !== 'xlsx') {
                echo "# OutraqHQ Employee Import Template\n";
                echo "# ROLE must be one of: employee, team_lead, manager, hr, admin\n";
                echo "# DEPARTMENT / TEAM: type the name; created automatically if new.\n";
                echo "# REPORTING_MANAGER_EMAIL: email of their manager (optional).\n";
                echo "# EMPLOYMENT_TYPE: full_time, part_time, contract, intern.\n";
                echo "# Delete these # comment lines OR leave them — the importer skips them.\n";
            }
            $writer->save('php://output');
        }, $filename, ['Content-Type' => $contentType]);
    }

    // ── Parse uploaded file into array of normalised rows ─────────────────────
    private function parseFile($file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if ($ext === 'json') {
            $data = json_decode(file_get_contents($file->getRealPath()), true);
            if (!is_array($data)) return [];
            return array_map(function ($row) {
                $out = [];
                foreach ($row as $k => $v) {
                    $out[strtolower(trim($k))] = is_string($v) ? trim($v) : $v;
                }
                return $out;
            }, $data);
        }

        // CSV or XLSX via PhpSpreadsheet — always read first sheet (Employees)
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet       = $spreadsheet->getSheet(0);
        $rows        = $sheet->toArray(null, true, true, false);

        // Drop blank rows and comment lines, then treat first remaining row as header
        $clean = [];
        foreach ($rows as $r) {
            $first = isset($r[0]) ? trim((string) $r[0]) : '';
            if ($first === '' && count(array_filter($r)) === 0) continue;
            if (str_starts_with($first, '#')) continue;
            $clean[] = $r;
        }
        if (empty($clean)) return [];

        $headers = array_map(fn($h) => strtolower(trim((string) $h)), array_shift($clean));

        $result = [];
        foreach ($clean as $r) {
            if (count(array_filter($r, fn($v) => trim((string) $v) !== '')) === 0) continue;
            $row = [];
            foreach ($headers as $i => $key) {
                if ($key === '') continue;
                $row[$key] = isset($r[$i]) ? trim((string) $r[$i]) : '';
            }
            $result[] = $row;
        }
        return $result;
    }

    // ── Parse pasted JSON string into normalised rows ─────────────────────────
    private function parseJsonString(string $json): array
    {
        $data = json_decode(trim($json), true);
        if (!is_array($data)) return [];

        // Allow top-level array OR {"employees": [...]}
        if (isset($data['employees']) && is_array($data['employees'])) {
            $data = $data['employees'];
        }

        $rows = [];
        foreach ($data as $item) {
            if (!is_array($item)) continue;
            $row = [];
            foreach ($item as $k => $v) {
                $row[strtolower(trim($k))] = is_string($v) ? trim($v) : $v;
            }
            $rows[] = $row;
        }
        return $rows;
    }

    // ── Phase 2: Upload → validate → preview ──────────────────────────────────
    public function preview(Request $request)
    {
        $this->guard();

        $mode = $request->input('mode', 'file');

        if ($mode === 'json') {
            $request->validate([
                'json_text' => ['required', 'string'],
            ]);
            $rows = $this->parseJsonString($request->input('json_text'));
        } else {
            $request->validate([
                'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls,json', 'max:5120'],
            ]);
            $rows = $this->parseFile($request->file('file'));
        }

        $orgId = auth()->user()->organization_id;

        if (empty($rows)) {
            return back()->withErrors(['file' => 'No data rows found in the file.']);
        }

        $existingEmails = User::pluck('email')->map(fn($e) => strtolower($e))->flip();
        $seenInFile     = [];
        $valid          = [];
        $errors         = [];

        foreach ($rows as $i => $row) {
            $rowErrors = [];
            $name      = $row['name']  ?? '';
            $email     = strtolower($row['email'] ?? '');
            $role      = strtolower($row['role'] ?? 'employee');

            if ($name === '') $rowErrors[] = 'missing name';

            if ($email === '') {
                $rowErrors[] = 'missing email';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = 'invalid email';
            } elseif ($existingEmails->has($email)) {
                $rowErrors[] = 'email already exists in system';
            } elseif (isset($seenInFile[$email])) {
                $rowErrors[] = 'duplicate email in file';
            }

            if (!in_array($role, $this->validRoles)) {
                $rowErrors[] = "invalid role '{$role}' (use: " . implode(', ', $this->validRoles) . ')';
            }

            if ($email !== '') $seenInFile[$email] = true;

            $clean = [
                'name'                    => $name,
                'email'                   => $email,
                'role'                    => $role,
                'designation'             => $row['designation']             ?? '',
                'department'              => $row['department']              ?? '',
                'team'                    => $row['team']                    ?? '',
                'reporting_manager_email' => strtolower($row['reporting_manager_email'] ?? ''),
                'phone'                   => $row['phone']                   ?? '',
                'employment_type'         => $row['employment_type']         ?? 'full_time',
            ];

            if (empty($rowErrors)) {
                $valid[] = $clean;
            } else {
                $errors[] = ['row' => $i + 1, 'data' => $clean, 'errors' => $rowErrors];
            }
        }

        session(['import_valid_rows' => $valid]);

        return view('import.preview', compact('valid', 'errors'));
    }

    // ── Phase 3: Confirm → create everything in a transaction ─────────────────
    public function run(Request $request)
    {
        $this->guard();

        $user  = auth()->user();
        $orgId = $user->organization_id;
        $rows  = session('import_valid_rows', []);

        if (empty($rows)) {
            return redirect()->route('import.employees')
                ->withErrors(['file' => 'No validated rows to import. Please upload again.']);
        }

        $created      = 0;
        $org          = Organization::find($orgId);
        $createdUsers = [];

        DB::beginTransaction();
        try {
            $deptCache = [];
            $teamCache = [];

            // Pass 1 — create users, departments, teams
            foreach ($rows as $row) {
                // Resolve / create department
                $deptId = null;
                if ($row['department'] !== '') {
                    $key = strtolower($row['department']);
                    if (!isset($deptCache[$key])) {
                        $dept = Department::firstOrCreate(
                            ['organization_id' => $orgId, 'name' => $row['department']],
                            ['slug' => Str::slug($row['department']) . '-' . Str::random(4), 'is_active' => true]
                        );
                        $deptCache[$key] = $dept->id;
                    }
                    $deptId = $deptCache[$key];
                }

                // Resolve / create team
                $teamId = null;
                if ($row['team'] !== '') {
                    $key = strtolower($row['team']);
                    if (!isset($teamCache[$key])) {
                        $team = Team::firstOrCreate(
                            ['organization_id' => $orgId, 'name' => $row['team']],
                            ['slug' => Str::slug($row['team']) . '-' . Str::random(4), 'department_id' => $deptId, 'is_active' => true]
                        );
                        $teamCache[$key] = $team->id;
                    }
                    $teamId = $teamCache[$key];
                }

                $newUser = User::create([
                    'name'              => $row['name'],
                    'email'             => $row['email'],
                    'password'          => Hash::make(Str::random(32)),
                    'organization_id'   => $orgId,
                    'department_id'     => $deptId,
                    'team_id'           => $teamId,
                    'role'              => $row['role'],
                    'designation'       => $row['designation']     ?: null,
                    'job_title'         => $row['designation']     ?: null,
                    'phone'             => $row['phone']           ?: null,
                    'employment_type'   => $row['employment_type'] ?: 'full_time',
                    'is_active'         => true,
                    'onboarding_status' => 'active',
                    'email_verified_at' => now(),
                    'approved_at'       => now(),
                ]);
                $newUser->syncRoles([$row['role']]);

                $createdUsers[$row['email']] = $newUser;
                $created++;
            }

            // Pass 2 — link reporting managers (all users now exist)
            foreach ($rows as $row) {
                $mgrEmail = $row['reporting_manager_email'];
                if ($mgrEmail === '') continue;

                $employee = $createdUsers[$row['email']] ?? null;
                if (!$employee) continue;

                $manager = $createdUsers[$mgrEmail]
                    ?? User::where('organization_id', $orgId)->where('email', $mgrEmail)->first();

                if ($manager) {
                    $employee->update(['reporting_manager_id' => $manager->id]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('import.employees')
                ->withErrors(['file' => 'Import failed and was rolled back: ' . $e->getMessage()]);
        }

        // Pass 3 — send password-setup emails (outside transaction)
        $emailed = 0;
        foreach ($createdUsers as $email => $u) {
            try {
                Password::sendResetLink(['email' => $email]);
                $emailed++;
            } catch (\Throwable $e) {
                // individual email failures are non-fatal
            }
        }

        session()->forget('import_valid_rows');

        return redirect()->route('import.employees')
            ->with('success', "{$created} employees imported. {$emailed} password-setup emails sent.");
    }
}
