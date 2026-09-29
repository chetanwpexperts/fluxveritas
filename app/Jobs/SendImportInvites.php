<?php

namespace App\Jobs;

use App\Http\Controllers\ImportInviteController;
use App\Mail\ImportInviteMail;
use App\Models\EmployeeImport;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/** Emails a set-password link to everyone an import created (not to updated people). */
class SendImportInvites implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(public EmployeeImport $import) {}

    public function handle(): void
    {
        $ids  = $this->import->summary['created_user_ids'] ?? [];
        $org  = $this->import->organization;
        $sent = 0;

        User::whereIn('id', $ids)
            ->where('organization_id', $this->import->organization_id)
            ->where('is_active', true)
            ->chunkById(200, function ($users) use ($org, &$sent) {
                foreach ($users as $user) {
                    try {
                        Mail::to($user->email)->send(new ImportInviteMail($user, $org, ImportInviteController::urlFor($user)));
                        $sent++;
                    } catch (\Throwable $e) {
                        Log::warning('Import invite failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                    }
                }
            });

        $summary = $this->import->summary ?? [];
        $summary['invites_sent'] = $sent;
        $this->import->update(['invites_sent_at' => now(), 'summary' => $summary]);
    }
}
