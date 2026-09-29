<?php

namespace App\Console\Commands;

use App\Jobs\SyncOrganizationGitHub;
use App\Models\Organization;
use App\Models\Project;
use App\Services\ModuleService;
use Illuminate\Console\Command;

class SyncGitHubCommand extends Command
{
    protected $signature = 'github:sync {--org= : Only this organization id}';

    protected $description = 'Queue a GitHub sync for every active organization with linked repositories';

    public function handle(ModuleService $modules): int
    {
        $orgIds = Project::withoutGlobalScopes()
            ->whereNotNull('github_owner')->where('github_owner', '!=', '')
            ->whereNotNull('github_repo')->where('github_repo', '!=', '')
            ->when($this->option('org'), fn ($q) => $q->where('organization_id', (int) $this->option('org')))
            ->distinct()->pluck('organization_id');

        $queued = 0;
        Organization::whereIn('id', $orgIds)->where('status', Organization::STATUS_ACTIVE)->get()
            ->filter(fn ($org) => $modules->hasModule($org->id, 'github_sync'))
            ->each(function ($org) use (&$queued) {
                SyncOrganizationGitHub::dispatch($org);
                $queued++;
            });

        $this->info("Queued GitHub sync for {$queued} organization(s).");

        return self::SUCCESS;
    }
}
