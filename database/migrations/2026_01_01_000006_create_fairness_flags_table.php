<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fairness_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flagged_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('flagged_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('flag_type');
            $table->decimal('confidence_score', 5, 4);
            $table->unsignedTinyInteger('layer')->default(1);
            $table->string('status')->default('pending');
            $table->json('evidence');
            $table->text('employee_response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamps();

            $table->index('organization_id');
            $table->index('status');
        });
    }
    public function down(): void {
        Schema::dropIfExists('fairness_flags');
    }
};