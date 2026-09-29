<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('increment_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('increment_policies')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('criteria_name');
            $table->string('criteria_label');
            $table->string('criteria_type');
            $table->string('data_source')->nullable();
            $table->decimal('weight_percent', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('increment_criteria'); }
};
