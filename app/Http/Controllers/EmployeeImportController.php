<?php

namespace App\Http\Controllers;

use App\Exceptions\WorkflowException;
use App\Jobs\RunEmployeeImport;
use App\Jobs\SendImportInvites;
use App\Models\EmployeeImport;
use App\Services\BillingService;
use App\Services\Import\EmployeeImportValidator;
use App\Services\Import\ImportFileReader;
use App\Services\Import\ImportIssueReport;
use App\Services\Import\ImportPresets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Smart Import: upload → map columns → preview → import (queued when large) → progress.
 * Owners, admins and HR only; every import belongs to the importer's organization.
 */
class EmployeeImportController extends Controller
{
    private function guard(): void
    {
        $user = auth()->user();
        abort_unless($user->organization_id && $user->hasAnyRole(['owner', 'admin', 'hr', 'super_admin']), 403,
            'Only owners, admins and HR can import employees.');
    }

    private function findImport(int $id): EmployeeImport
    {
        $this->guard();

        return EmployeeImport::where('organization_id', auth()->user()->organization_id)->findOrFail($id);
    }

    // ── 1. Upload ────────────────────────────────────────────────────────────

    public function index()
    {
        $this->guard();

        return view('import.employees', [
            'presets' => ImportPresets::PRESETS,
            'recent'  => EmployeeImport::where('organization_id', auth()->user()->organization_id)->latest()->take(10)->get(),
            'maxRows' => config('imports.max_rows'),
        ]);
    }

    public function upload(Request $request, ImportFileReader $reader): RedirectResponse
    {
        $this->guard();

        $request->validate([
            'file'   => ['required', 'file', 'mimes:' . implode(',', ImportFileReader::EXTENSIONS), 'max:' . config('imports.max_file_kb')],
            'preset' => ['required', Rule::in(array_keys(ImportPresets::PRESETS))],
        ]);

        $user = auth()->user();
        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension()) ?: 'csv';
        $path = $file->storeAs("imports/{$user->organization_id}", Str::uuid() . ".{$ext}", 'local');

        try {
            $data = $reader->read(Storage::disk('local')->path($path), $ext);
        } catch (WorkflowException $e) {
            Storage::disk('local')->delete($path);
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        if (!$data['rows']) {
            Storage::disk('local')->delete($path);
            return back()->withErrors(['file' => 'The file has a header row but no data rows.']);
        }
        if (count($data['rows']) > config('imports.max_rows')) {
            Storage::disk('local')->delete($path);
            return back()->withErrors(['file' => 'The file has more than ' . number_format(config('imports.max_rows')) . ' rows. Split it into smaller files.']);
        }

        $suggested = ImportPresets::suggest($data['headers'], $request->preset, $user->organization_id);

        $import = EmployeeImport::create([
            'organization_id' => $user->organization_id,
            'user_id'         => $user->id,
            'original_name'   => Str::limit($file->getClientOriginalName(), 200, ''),
            'file_path'       => $path,
            'preset'          => $request->preset,
            'headers'         => $data['headers'],
            'mapping'         => array_map(fn ($s) => $s['field'], $suggested),
            'options'         => ['duplicates' => 'skip', 'send_invites' => 'now', 'skip_inactive' => true, 'date_format' => 'dmy'],
            'total_rows'      => count($data['rows']),
            'summary'         => ['suggested' => $suggested, 'samples' => array_slice(array_column($data['rows'], 'cells'), 0, 3)],
        ]);

        return redirect()->route('import.employees.mapping', $import);
    }

    // ── 2. Map columns ───────────────────────────────────────────────────────

    public function mapping(int $import)
    {
        $import = $this->findImport($import);
        abort_if($import->isFinished() || in_array($import->status, ['queued', 'running'], true), 404);

        return view('import.mapping', [
            'import'  => $import,
            'fields'  => ImportPresets::fieldLabels($import->organization_id),
            'presets' => ImportPresets::PRESETS,
        ]);
    }

    public function saveMapping(Request $request, int $import, EmployeeImportValidator $validator, ImportIssueReport $report): RedirectResponse
    {
        $import = $this->findImport($import);
        abort_if($import->isFinished() || in_array($import->status, ['queued', 'running'], true), 404);

        $fields = array_keys(ImportPresets::fieldLabels($import->organization_id));
        $data   = $request->validate([
            'mapping'       => ['array'],
            'mapping.*'     => ['nullable', Rule::in($fields)],
            'duplicates'    => ['required', Rule::in(['skip', 'update'])],
            'send_invites'  => ['required', Rule::in(['now', 'later'])],
            'date_format'   => ['required', Rule::in(['dmy', 'mdy'])],
            'skip_inactive' => ['boolean'],
        ]);

        // Keep only columns that exist; each field once
        $mapping = [];
        $used    = [];
        foreach (array_keys($import->headers ?? []) as $i) {
            $field = $data['mapping'][$i] ?? null;
            $mapping[$i] = ($field && !isset($used[$field])) ? $field : null;
            if ($field) {
                $used[$field] = true;
            }
        }

        if (!isset($used['email'])) {
            return back()->withErrors(['mapping' => 'Choose which column holds the work email.'])->withInput();
        }
        if (!isset($used['full_name']) && !isset($used['first_name'])) {
            return back()->withErrors(['mapping' => 'Choose a column for the full name, or for the first name.'])->withInput();
        }

        $import->update([
            'mapping' => $mapping,
            'options' => [
                'duplicates'    => $data['duplicates'],
                'send_invites'  => $data['send_invites'],
                'date_format'   => $data['date_format'],
                'skip_inactive' => $request->boolean('skip_inactive'),
            ],
        ]);

        $result = $validator->validate($import, auth()->user());

        $import->update([
            'status'      => 'validated',
            'total_rows'  => $result['stats']['total'],
            'errors_path' => $report->write($import, $result['issues']),
            'summary'     => array_merge($import->summary ?? [], [
                'preview' => [
                    'stats'  => $result['stats'],
                    'new'    => $result['new'],
                    'issues' => array_slice($result['issues'], 0, 200),
                    'sample' => array_map(fn ($r) => array_intersect_key($r, array_flip(['line', 'action', 'name', 'email', 'role', 'department', 'team', 'designation', 'manager_email', 'manager_name', 'join_date'])), array_slice($result['rows'], 0, 20)),
                ],
            ]),
        ]);

        return redirect()->route('import.employees.preview', $import);
    }

    // ── 3. Preview ───────────────────────────────────────────────────────────

    public function preview(int $import)
    {
        $import = $this->findImport($import);
        abort_unless($import->status === 'validated', 404);

        $preview = $import->summary['preview'] ?? [];

        return view('import.preview', [
            'import'    => $import,
            'preview'   => $preview,
            'seatError' => app(BillingService::class)->seatLimitError($import->organization, $preview['stats']['create'] ?? 0),
            'queued'    => ($preview['stats']['total'] ?? 0) > config('imports.queue_threshold'),
        ]);
    }

    // ── 4. Import ────────────────────────────────────────────────────────────

    public function run(int $import): RedirectResponse
    {
        $import = $this->findImport($import);
        abort_unless($import->status === 'validated', 404);

        $create = $import->summary['preview']['stats']['create'] ?? 0;
        if ($limit = app(BillingService::class)->seatLimitError($import->organization, $create)) {
            return back()->withErrors(['import' => $limit]);
        }

        $import->update(['status' => 'queued', 'processed_rows' => 0]);

        $import->total_rows > config('imports.queue_threshold')
            ? RunEmployeeImport::dispatch($import)
            : RunEmployeeImport::dispatchSync($import);

        return redirect()->route('import.employees.show', $import);
    }

    public function show(int $import)
    {
        return view('import.progress', ['import' => $this->findImport($import)]);
    }

    public function progress(int $import): JsonResponse
    {
        $import = $this->findImport($import);

        return response()->json([
            'status'    => $import->status,
            'percent'   => $import->status === 'completed' ? 100 : $import->progressPercent(),
            'processed' => $import->processed_rows,
            'total'     => $import->total_rows,
            'finished'  => $import->isFinished(),
        ]);
    }

    public function issues(int $import)
    {
        $import = $this->findImport($import);
        abort_unless($import->errors_path && Storage::disk('local')->exists($import->errors_path), 404);

        return Storage::disk('local')->download($import->errors_path, "import-{$import->id}-issues.csv", ['Content-Type' => 'text/csv']);
    }

    public function sendInvites(int $import): RedirectResponse
    {
        $import = $this->findImport($import);
        abort_unless($import->status === 'completed' && !$import->invites_sent_at, 404);

        SendImportInvites::dispatch($import);
        $import->update(['invites_sent_at' => now()]);

        return back()->with('success', 'Invitations are being sent to ' . count($import->summary['created_user_ids'] ?? []) . ' people.');
    }

    // ── Template ─────────────────────────────────────────────────────────────

    public function template()
    {
        $this->guard();

        $rows = [
            ['name', 'email', 'role', 'department', 'team', 'designation', 'reporting_manager_email', 'join_date', 'phone', 'employment_type', 'work_location'],
            ['Engineering Lead', 'lead@yourcompany.com', 'team_lead', 'Engineering', 'Web Team', 'Engineering Lead', '', '2024-04-01', '9876500000', 'full_time', 'Bengaluru'],
            ['Software Developer', 'dev@yourcompany.com', 'employee', 'Engineering', 'Web Team', 'Software Developer', 'lead@yourcompany.com', '2025-01-15', '9876543210', 'full_time', 'Remote'],
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row, ',', '"', '\\');
            }
            fclose($out);
        }, 'outraqhq-employee-import-template.csv', ['Content-Type' => 'text/csv']);
    }
}
