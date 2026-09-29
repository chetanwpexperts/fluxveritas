<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocker_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blocker_id')->constrained('blockers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('response_type'); // acknowledged, disputed, escalated, resolved, commented
            $table->text('message');
            $table->timestamps();

            $table->index(['blocker_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocker_responses');
    }
};
