<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('rule_name');
            $table->string('rule_label');
            $table->text('description');
            $table->boolean('is_enabled')->default(true);
            $table->string('trigger_condition');
            $table->integer('trigger_value')->default(1);
            $table->boolean('notify_user')->default(true);
            $table->boolean('notify_manager')->default(true);
            $table->boolean('notify_owner')->default(false);
            $table->integer('cooldown_hours')->default(24);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_rules');
    }
};
