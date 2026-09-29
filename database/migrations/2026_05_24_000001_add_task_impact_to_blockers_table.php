<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blockers', function (Blueprint $table) {
            $table->unsignedBigInteger('task_id')->nullable()->after('project_id');
            $table->foreign('task_id')->references('id')->on('tasks')->nullOnDelete();
            $table->string('impact_level')->default('just_me')->after('task_id');
        });
    }

    public function down(): void
    {
        Schema::table('blockers', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
            $table->dropColumn(['task_id', 'impact_level']);
        });
    }
};
