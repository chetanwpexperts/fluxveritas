<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * GitHub sync used to store pull requests as "pull_request", which the
 * increment calculator never counted (it counts pr_opened / pr_merged).
 * Merged PRs were stored with quality_score 1.0.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('activities')->where('event_type', 'pull_request')->where('quality_score', '>=', 1)->update(['event_type' => 'pr_merged']);
        DB::table('activities')->where('event_type', 'pull_request')->update(['event_type' => 'pr_opened']);
    }

    public function down(): void
    {
        DB::table('activities')->whereIn('event_type', ['pr_opened', 'pr_merged'])->where('source', 'github')->update(['event_type' => 'pull_request']);
    }
};
