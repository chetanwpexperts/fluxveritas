<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->enum('category', [
                'policy', 'handbook', 'offer_letter', 'appraisal',
                'salary_slip', 'experience_letter', 'nda', 'other',
            ]);
            $table->enum('audience', ['org', 'department', 'personal']);
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->bigInteger('file_size');
            $table->string('file_type', 50);
            $table->string('version', 20)->default('1.0');
            $table->boolean('is_active')->default(true);
            $table->integer('download_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
