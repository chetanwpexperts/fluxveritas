<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('category');
            $table->string('department_type')->nullable();
            $table->string('seniority_level')->nullable();
            $table->boolean('requires_github')->default(false);
            $table->json('metric_weights')->nullable();
            $table->json('work_log_categories')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_template')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['organization_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designations');
    }
};
