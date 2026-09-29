<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('increment_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('policy_id')->constrained('increment_policies')->cascadeOnDelete();
            $table->integer('review_year');
            $table->string('review_period');
            $table->json('months_included')->nullable();
            $table->json('months_excluded')->nullable();
            $table->decimal('avg_score', 8, 4)->default(0);
            $table->decimal('recommended_increment', 5, 2)->default(0);
            $table->decimal('manager_recommendation', 5, 2)->nullable();
            $table->text('manager_notes')->nullable();
            $table->decimal('final_increment', 5, 2)->nullable();
            $table->text('ceo_notes')->nullable();
            $table->text('override_reason')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('manager_reviewed_at')->nullable();
            $table->timestamp('ceo_approved_at')->nullable();
            $table->timestamp('employee_notified_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('increment_reviews'); }
};
