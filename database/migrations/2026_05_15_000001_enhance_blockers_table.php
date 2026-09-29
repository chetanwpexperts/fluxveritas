<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blockers', function (Blueprint $table) {
            $table->string('external_person_name')->nullable()->after('blocking_user_id');
            $table->string('external_person_company')->nullable()->after('external_person_name');
            $table->string('external_person_contact')->nullable()->after('external_person_company');
            $table->text('evidence_notes')->nullable()->after('external_person_contact');
            $table->boolean('ownership_disputed')->default(false)->after('evidence_notes');
            $table->text('dispute_reason')->nullable()->after('ownership_disputed');
            $table->timestamp('dispute_raised_at')->nullable()->after('dispute_reason');
            $table->timestamp('dispute_resolved_at')->nullable()->after('dispute_raised_at');
            $table->text('resolution_proof')->nullable()->after('dispute_resolved_at');
            $table->boolean('resolved_by_external')->default(false)->after('resolution_proof');
            $table->integer('days_to_resolve')->nullable()->after('resolved_by_external');
            $table->timestamp('reminder_sent_at')->nullable()->after('days_to_resolve');

            $table->index('ownership_disputed');
        });
    }

    public function down(): void
    {
        Schema::table('blockers', function (Blueprint $table) {
            $table->dropColumn([
                'external_person_name',
                'external_person_company',
                'external_person_contact',
                'evidence_notes',
                'ownership_disputed',
                'dispute_reason',
                'dispute_raised_at',
                'dispute_resolved_at',
                'resolution_proof',
                'resolved_by_external',
                'days_to_resolve',
                'reminder_sent_at',
            ]);
        });
    }
};
