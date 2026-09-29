<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'autopilot_settings')) {
                $table->json('autopilot_settings')->nullable()->after('billing_status');
            }
        });

        Schema::table('blockers', function (Blueprint $table) {
            if (!Schema::hasColumn('blockers', 'delay_hours')) {
                $table->integer('delay_hours')->default(0)->after('status');
            }
            if (!Schema::hasColumn('blockers', 'estimated_delay_cost')) {
                $table->decimal('estimated_delay_cost', 10, 2)->default(0.00)->after('delay_hours');
            }
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (Schema::hasColumn('organizations', 'autopilot_settings')) {
                $table->dropColumn('autopilot_settings');
            }
        });

        Schema::table('blockers', function (Blueprint $table) {
            if (Schema::hasColumn('blockers', 'delay_hours')) {
                $table->dropColumn(['delay_hours', 'estimated_delay_cost']);
            }
        });
    }
};
