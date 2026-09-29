<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('task_id')
                ->nullable()
                ->after('project_id');
            $table->foreign('task_id')
                ->references('id')
                ->on('tasks')
                ->onDelete('set null');
            $table->index('task_id');
        });
    }

    public function down(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
            $table->dropIndex(['task_id']);
            $table->dropColumn('task_id');
        });
    }
};
