<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manager_accountability_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager_id');
            $table->foreign('manager_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->string('review_period');
            $table->year('review_year');
            $table->integer('feedbacks_due')->default(0);
            $table->integer('feedbacks_submitted')->default(0);
            $table->integer('feedbacks_on_time')->default(0);
            $table->integer('bias_flags')->default(0);
            $table->float('accountability_score')->default(100);
            $table->json('breakdown')->nullable();
            $table->timestamps();

            $table->unique(
                ['manager_id', 'review_period', 'review_year'],
                'mas_manager_period_year_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_accountability_scores');
    }
};
