<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('increment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('policy_id')->constrained('increment_policies')->cascadeOnDelete();
            $table->date('score_month');
            $table->decimal('raw_score', 8, 4)->default(0);
            $table->decimal('weighted_score', 8, 4)->default(0);
            $table->json('criteria_breakdown')->nullable();
            $table->decimal('anti_gaming_penalty', 5, 2)->default(0);
            $table->decimal('final_score', 8, 4)->default(0);
            $table->boolean('is_adjusted')->default(false);
            $table->text('adjustment_reason')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'score_month', 'policy_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('increment_scores'); }
};
