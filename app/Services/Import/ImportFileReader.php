<?php

namespace App\Services\Import;

use App\Exceptions\WorkflowException;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Reads CSV and XLSX files as a header row plus data rows.
 *
 * - CSV: delimiter detected (comma, semicolon, tab), UTF-8 BOM stripped.
 * - XLSX/XLS: first sheet, raw values (dates stay Excel serial numbers so the
 *   importer can convert them reliably instead of trusting display formats).
 * - Blank rows and lines starting with "#" are skipped.
 * Each row keeps its line number in the file for error reports.
 */
class ImportFileReader
{
    public const EXTENSIONS = ['csv', 'txt', 'xlsx', 'xls'];

    /** @return array{headers: string[], rows: array<int, array{line: int, cells: array<int, string>}>} */
    public function read(string $path, string $extension): array
    {
        $extension = strtolower($extension);
        $lines     = in_array($extension, ['xlsx', 'xls'], true) ? $this->spreadsheetLines($path) : $this->csvLines($path);

        $headers = null;
        $rows    = [];
        foreach ($lines as $lineNo => $cells) {
            $cells = array_map(fn ($v) => $this->cell($v), $cells);
            $first = $cells[0] ?? '';

            if (count(array_filter($cells, fn ($v) => $v !== '')) === 0 || str_starts_with($first, '#')) {
                continue;
            }

            if ($headers === null) {
                $headers = array_map(fn ($h) => trim(preg_replace('/^\xEF\xBB\xBF/', '', $h)), $cells);
                continue;
            }

            $rows[] = ['line' => $lineNo, 'cells' => $cells];
        }

        if (!$headers) {
            throw new WorkflowException('The file is empty or has no header row.', 'file');
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /** @return iterable<int, array> line number => cells */
    private function csvLines(string $path): iterable
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new WorkflowException('The file could not be read.', 'file');
        }

        $delimiter = $this->detectDelimiter((string) fgets($handle));
        rewind($handle);

        $lineNo = 0;
        while (($cells = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $lineNo++;
            if ($cells === [null]) {
                continue;
            }
            yield $lineNo => $this->toUtf8($cells);
        }
        fclose($handle);
    }

    /** @return iterable<int, array> */
    private function spreadsheetLines(string $path): iterable
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($path)->getSheet(0);
        } catch (\Throwable) {
            throw new WorkflowException('This spreadsheet could not be opened. Save it as .xlsx or .csv and try again.', 'file');
        }

        foreach ($sheet->toArray(null, true, false, false) as $i => $cells) {
            yield $i + 1 => $cells;
        }
    }

    private function detectDelimiter(string $firstLine): string
    {
        $counts = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
        arsort($counts);

        return (string) array_key_first($counts);
    }

    private function toUtf8(array $cells): array
    {
        return array_map(function ($v) {
            $v = (string) $v;
            return mb_check_encoding($v, 'UTF-8') ? $v : mb_convert_encoding($v, 'UTF-8', 'Windows-1252');
        }, $cells);
    }

    private function cell(mixed $value): string
    {
        if (is_float($value) && floor($value) === $value && abs($value) < 1e15) {
            return (string) (int) $value; // 9876543210.0 → "9876543210"
        }

        return trim((string) ($value ?? ''));
    }
}
