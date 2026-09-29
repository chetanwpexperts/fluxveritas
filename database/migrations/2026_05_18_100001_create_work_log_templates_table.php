<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_log_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->integer('output_value')->default(5);
            $table->longText('tags')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'usage_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_log_templates');
    }
};
