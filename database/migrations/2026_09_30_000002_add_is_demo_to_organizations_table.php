<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks QA/demo organizations created by DemoDataSeeder, so they can be
 * found and removed with `php artisan demo:purge` without touching real data.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('organizations', 'is_demo')) {
            return;
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('status')->index();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('organizations', 'is_demo')) {
            return;
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex(['is_demo']);
            $table->dropColumn('is_demo');
        });
    }
};
