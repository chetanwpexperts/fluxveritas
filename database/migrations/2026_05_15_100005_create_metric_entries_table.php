<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('metric_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('metric_id')->constrained('department_metrics')->cascadeOnDelete();
            $table->decimal('value', 10, 2);
            $table->date('entry_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'entry_date']);
            $table->index(['department_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metric_entries');
    }
};
