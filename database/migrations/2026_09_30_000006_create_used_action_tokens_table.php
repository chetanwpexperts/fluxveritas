<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Makes emailed one-click action links single use (see MagicActionTokenService). */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('used_action_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('jti', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action_type');
            $table->timestamp('used_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('used_action_tokens');
    }
};
