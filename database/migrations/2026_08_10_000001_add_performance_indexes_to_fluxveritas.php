<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->index(['organization_id', 'occurred_at', 'event_type'], 'idx_act_org_date_type');
        });

        Schema::table('work_logs', function (Blueprint $table) {
            $table->index(['organization_id', 'user_id', 'log_date'], 'idx_wl_org_user_date');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['assigned_to', 'status', 'created_at'], 'idx_tasks_user_status_date');
        });

        Schema::table('blockers', function (Blueprint $table) {
            $table->index(['organization_id', 'status', 'blocked_user_id'], 'idx_blockers_org_status_user');
        });

        Schema::table('increment_scores', function (Blueprint $table) {
            $table->index(['organization_id', 'score_month', 'user_id'], 'idx_inc_org_month_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('idx_act_org_date_type');
        });

        Schema::table('work_logs', function (Blueprint $table) {
            $table->dropIndex('idx_wl_org_user_date');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_user_status_date');
        });

        Schema::table('blockers', function (Blueprint $table) {
            $table->dropIndex('idx_blockers_org_status_user');
        });

        Schema::table('increment_scores', function (Blueprint $table) {
            $table->dropIndex('idx_inc_org_month_user');
        });
    }
};
