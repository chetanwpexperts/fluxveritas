<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Actions Outy has prepared but that only run after the user presses Confirm
 * on the card in the chat widget. Single use, owned by one user, short-lived.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('outy_pending_actions', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tool');
            $table->json('payload');
            $table->json('summary');
            $table->string('status')->default('pending'); // pending | confirmed | cancelled | expired | failed
            $table->text('result')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outy_pending_actions');
    }
};
