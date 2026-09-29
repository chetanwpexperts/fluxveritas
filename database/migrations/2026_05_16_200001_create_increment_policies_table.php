<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('increment_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('max_increment_percent', 5, 2)->default(30.00);
            $table->string('review_period')->default('annual');
            $table->integer('review_month')->default(12);
            $table->integer('minimum_months_required')->default(6);
            $table->decimal('minimum_score_for_increment', 5, 2)->default(40.00);
            $table->boolean('anti_gaming_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('increment_policies'); }
};
