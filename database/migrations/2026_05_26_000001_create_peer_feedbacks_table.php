<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peer_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedBigInteger('reviewer_id')->nullable();
            $table->foreign('reviewer_id')->references('id')->on('users')->nullOnDelete();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->string('review_period');
            $table->year('review_year');
            $table->tinyInteger('collaboration_score')->nullable();
            $table->tinyInteger('reliability_score')->nullable();
            $table->tinyInteger('knowledge_score')->nullable();
            $table->tinyInteger('helpfulness_score')->nullable();
            $table->text('strength')->nullable();
            $table->text('improvement')->nullable();
            $table->string('token')->unique();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('is_submitted')->default(false);
            $table->timestamps();

            $table->unique(
                ['employee_id', 'reviewer_id', 'review_period', 'review_year'],
                'pf_emp_rev_period_year_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peer_feedbacks');
    }
};
