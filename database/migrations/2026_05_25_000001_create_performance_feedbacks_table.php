<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('performance_feedbacks');
        Schema::create('performance_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedBigInteger('manager_id');
            $table->foreign('manager_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->string('review_period'); // Q1/Q2/Q3/Q4/Annual
            $table->year('review_year');
            $table->tinyInteger('delivery_score')->default(2);
            $table->tinyInteger('timeliness_score')->default(2);
            $table->tinyInteger('availability_score')->default(2);
            $table->tinyInteger('collaboration_score')->default(2);
            $table->text('notable_achievement')->nullable();
            $table->text('area_of_improvement')->nullable();
            $table->text('special_circumstances')->nullable();
            $table->boolean('manager_confirmed')->default(false);
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->timestamp('employee_viewed_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'review_period', 'review_year'], 'pf_employee_period_year_unique');
            $table->index(['organization_id', 'review_period', 'review_year'], 'pf_org_period_year_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_feedbacks');
    }
};
