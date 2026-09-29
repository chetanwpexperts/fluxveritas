<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('depends_on_task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('depends_on_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('dependency_type'); // finish_to_start, approval_needed, review_needed, external_input
            $table->string('status')->default('waiting'); // waiting, unblocked, skipped
            $table->text('notes')->nullable();
            $table->dateTime('unblocked_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
    }
};
