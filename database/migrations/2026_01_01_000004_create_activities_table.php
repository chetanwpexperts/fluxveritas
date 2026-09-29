<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('organization_id');
            $table->string('source')->default('github');
            $table->string('event_type');
            $table->string('external_id')->nullable();
            $table->json('metadata');
            $table->decimal('complexity_score', 5, 2)->nullable();
            $table->decimal('impact_score', 5, 2)->nullable();
            $table->decimal('quality_score', 5, 2)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['user_id', 'occurred_at']);
            $table->index('organization_id');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });
    }
    public function down(): void {
        Schema::dropIfExists('activities');
    }
};