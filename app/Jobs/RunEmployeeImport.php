<?php

namespace App\Jobs;

use App\Exceptions\WorkflowException;
use App\Models\EmployeeImport;
use App\Services\Import\EmployeeImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunEmployeeImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(public EmployeeImport $import) {}

    public function handle(EmployeeImporter $importer): void
    {
        if ($this->import->isFinished() || $this->import->status === 'running') {
            return;
        }

        try {
            $import = $importer->run($this->import);
        } catch (WorkflowException $e) {
            $this->import->update(['status' => 'failed', 'failure_message' => $e->getMessage(), 'finished_at' => now()]);
            return;
        }

        if ($import->option('send_invites') === 'now') {
            SendImportInvites::dispatch($import);
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->import->update([
            'status' => 'failed', 'finished_at' => now(),
            'failure_message' => 'The import stopped unexpectedly. People saved before the error were kept — run the same file again with "skip existing" to finish.',
        ]);
    }
}
