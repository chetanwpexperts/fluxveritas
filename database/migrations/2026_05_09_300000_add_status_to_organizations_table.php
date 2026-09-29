<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('status')->default('active')->after('slug');
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('approved_at');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete()->after('approved_by');
            $table->text('notes')->nullable()->after('owner_id');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['owner_id']);
            $table->dropColumn(['status', 'approved_at', 'approved_by', 'owner_id', 'notes']);
        });
    }
};
