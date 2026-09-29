<?php

namespace App\Services\Import;

use App\Models\EmployeeImport;
use App\Support\CsvCell;
use Illuminate\Support\Facades\Storage;

/** Downloadable CSV of every row that was skipped, failed or needs attention. */
class ImportIssueReport
{
    public function write(EmployeeImport $import, array $issues): ?string
    {
        if (!$issues) {
            return null;
        }

        $path   = "imports/{$import->organization_id}/import-{$import->id}-issues.csv";
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Line in file', 'Email', 'Name', 'Result', 'Details'], ',', '"', '\\');

        foreach (collect($issues)->sortBy('line') as $issue) {
            fputcsv($handle, CsvCell::row([
                $issue['line'], $issue['email'], $issue['name'], ucfirst($issue['level']), implode('; ', $issue['messages']),
            ]), ',', '"', '\\');
        }

        rewind($handle);
        Storage::disk('local')->put($path, stream_get_contents($handle));
        fclose($handle);

        return $path;
    }
}
