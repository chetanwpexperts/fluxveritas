<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blockers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('blocked_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('blocking_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('blocker_type'); // internal_person, external_vendor, meeting_required, waiting_approval, dependency_task, other
            $table->string('title');
            $table->text('description');
            $table->string('status')->default('open');   // open, resolved, escalated
            $table->string('priority')->default('medium'); // low, medium, high, critical
            $table->dateTime('due_date')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['blocked_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blockers');
    }
};
