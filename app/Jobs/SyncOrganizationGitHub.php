<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Services\GitHub\GitHubSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Syncs one organization's GitHub activity. At most one queued per organization. */
class SyncOrganizationGitHub implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public int $tries = 1;

    public int $uniqueFor = 900;

    public function __construct(public Organization $organization) {}

    public function uniqueId(): string
    {
        return (string) $this->organization->id;
    }

    public function handle(GitHubSyncService $sync): void
    {
        $sync->syncOrganization($this->organization);
    }
}
