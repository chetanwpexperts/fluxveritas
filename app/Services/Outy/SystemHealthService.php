<?php

namespace App\Services\Outy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Read-only platform health snapshot for Outy. Unlike AutonomousAgent's health
 * check it never alerts, clears logs or writes anything.
 */
class SystemHealthService
{
    private const LOG_TAIL_BYTES = 2_000_000;

    public function snapshot(bool $includeMessages): array
    {
        $errors = $this->recentErrors();

        $health = [
            'database_ok'  => $this->databaseOk(),
            'queue'        => [
                'pending_jobs' => Schema::hasTable('jobs') ? DB::table('jobs')->count() : null,
                'failed_jobs'  => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : null,
            ],
            'errors_last_24h' => $errors['total'],
            'disk'         => $this->disk(),
            'checked_at'   => now()->setTimezone('Asia/Kolkata')->format('j M Y, g:i A') . ' IST',
        ];

        if ($includeMessages) {
            $health['top_errors']         = $errors['top'];
            $health['recent_failed_jobs'] = $this->recentFailedJobs();
        }

        return $health;
    }

    private function databaseOk(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array{total:int, top: array<int, array{message:string, count:int}>} */
    private function recentErrors(): array
    {
        $since  = now()->subDay();
        $counts = [];

        foreach (glob(storage_path('logs/laravel*.log')) ?: [] as $file) {
            if (filemtime($file) < $since->getTimestamp()) {
                continue;
            }
            foreach ($this->tail($file) as $line) {
                if (!preg_match('/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})[^\]]*\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY): (.*)$/', $line, $m)) {
                    continue;
                }
                if (strtotime($m[1]) < $since->getTimestamp()) {
                    continue;
                }
                // Message only: drop the JSON context / stack trace that follows it
                $message = Str::limit(trim(preg_split('/ \{"|\[stacktrace\]| at \/.+:\d+/', $m[3])[0]), 160);
                $counts[$message] = ($counts[$message] ?? 0) + 1;
            }
        }

        arsort($counts);

        return [
            'total' => array_sum($counts),
            'top'   => collect($counts)->take(5)->map(fn ($count, $message) => ['message' => $message, 'count' => $count])->values()->all(),
        ];
    }

    /** Last part of a log file, as lines. */
    private function tail(string $file): array
    {
        $handle = @fopen($file, 'r');
        if (!$handle) {
            return [];
        }
        $size = filesize($file);
        fseek($handle, max(0, $size - self::LOG_TAIL_BYTES));
        $data = stream_get_contents($handle);
        fclose($handle);

        return explode("\n", (string) $data);
    }

    private function recentFailedJobs(): array
    {
        if (!Schema::hasTable('failed_jobs')) {
            return [];
        }

        return DB::table('failed_jobs')->orderByDesc('failed_at')->take(5)->get(['queue', 'exception', 'failed_at'])
            ->map(fn ($job) => [
                'queue'     => $job->queue,
                'failed_at' => (string) $job->failed_at,
                'error'     => Str::limit(strtok((string) $job->exception, "\n"), 200),
            ])->all();
    }

    private function disk(): array
    {
        $free  = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());

        if (!$free || !$total) {
            return ['free_gb' => null, 'used_percent' => null];
        }

        return [
            'free_gb'      => round($free / 1_073_741_824, 1),
            'used_percent' => (int) round(100 - $free / $total * 100),
        ];
    }
}
