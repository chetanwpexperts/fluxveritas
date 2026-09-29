<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->timestamp('plan_expires_at')->nullable()->after('plan');
            $table->string('billing_status')->default('free')->after('plan_expires_at');
            // billing_status: free | active | expired
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['plan_expires_at', 'billing_status']);
        });
    }
};
