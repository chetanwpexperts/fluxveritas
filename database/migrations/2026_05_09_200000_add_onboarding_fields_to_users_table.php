<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Default 'active' so existing users are not locked out
            $table->string('onboarding_status')->default('active')->after('is_active');
            $table->string('onboarding_type')->nullable()->after('onboarding_status');
            $table->text('rejection_reason')->nullable()->after('onboarding_type');
            $table->timestamp('approved_at')->nullable()->after('rejection_reason');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'onboarding_status',
                'onboarding_type',
                'rejection_reason',
                'approved_at',
                'approved_by',
            ]);
        });
    }
};
