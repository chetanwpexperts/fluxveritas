<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_bias_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('feedback_id');
            $table->foreign('feedback_id')->references('id')->on('performance_feedbacks')->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedBigInteger('manager_id');
            $table->foreign('manager_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->string('review_period');
            $table->year('review_year');
            $table->float('ai_score')->nullable();
            $table->float('manager_implied_score')->nullable();
            $table->float('peer_score')->nullable();
            $table->integer('peer_count')->default(0);
            $table->float('deviation')->nullable();
            $table->boolean('bias_detected')->default(false);
            $table->integer('bias_confidence')->default(0);
            $table->string('bias_type')->nullable();
            $table->text('bias_reason')->nullable();
            $table->json('evidence')->nullable();
            $table->boolean('ceo_reviewed')->default(false);
            $table->timestamp('ceo_reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'review_period', 'review_year'], 'fbr_org_period_year_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_bias_reports');
    }
};
