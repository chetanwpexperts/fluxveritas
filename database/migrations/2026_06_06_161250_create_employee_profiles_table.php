<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('phone', 30)->nullable();
            $table->string('designation')->nullable();
            $table->date('date_of_joining')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->json('skills')->nullable();
            $table->text('bio')->nullable();
            $table->string('github_username')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('profile_photo')->nullable();
            $table->boolean('is_directory_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
