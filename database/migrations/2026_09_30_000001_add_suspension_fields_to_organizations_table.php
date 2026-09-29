<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * organizations.status already exists (active | pending | suspended) from
 * 2026_05_09_300000_add_status_to_organizations_table — this adds who/when/why.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('status');
            $table->foreignId('suspended_by')->nullable()->after('suspended_at')->constrained('users')->nullOnDelete();
            $table->text('suspension_reason')->nullable()->after('suspended_by');
            $table->index('status');
        });

        // Orgs suspended before this migration kept their reason in `notes`
        DB::table('organizations')
            ->where('status', 'suspended')
            ->update([
                'suspension_reason' => DB::raw('notes'),
                'suspended_at'      => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('suspended_by');
            $table->dropColumn(['suspended_at', 'suspension_reason']);
        });
    }
};
