<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Demo\DemoDataCleaner;
use Illuminate\Console\Command;

class PurgeDemoDataCommand extends Command
{
    protected $signature = 'demo:purge
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Delete every demo organization (is_demo = true) and everything linked to it';

    public function handle(DemoDataCleaner $cleaner): int
    {
        $orgs = Organization::demo()->withCount('users')->orderBy('name')->get();

        if ($orgs->isEmpty()) {
            $this->info('No demo organizations found. Nothing to delete.');
            return self::SUCCESS;
        }

        $this->warn('This permanently deletes these demo organizations, their users and all their data:');
        $this->table(['ID', 'Organization', 'Slug', 'Users'], $orgs->map(fn ($o) => [$o->id, $o->name, $o->slug, $o->users_count]));
        $this->line('Organizations that are not marked is_demo are never touched.');

        if (!$this->option('force') && !$this->confirm('Delete all of the above?', false)) {
            $this->info('Cancelled. Nothing was deleted.');
            return self::SUCCESS;
        }

        $result = $cleaner->purge($orgs->pluck('id'));

        $this->info("Deleted {$result['organizations']} demo organization(s) and {$result['users']} user(s).");

        return self::SUCCESS;
    }
}
