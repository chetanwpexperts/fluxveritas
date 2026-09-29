<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->date('log_date');
            $table->string('category');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->integer('output_value')->default(5);
            $table->boolean('is_billable')->default(false);
            $table->json('tags')->nullable();
            $table->text('evidence')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'log_date']);
            $table->index(['organization_id', 'log_date']);
            $table->index(['department_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_logs');
    }
};
