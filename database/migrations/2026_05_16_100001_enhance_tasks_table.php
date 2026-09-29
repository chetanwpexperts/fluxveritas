<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('ticket_number')->nullable()->unique()->after('id');
            $table->string('type')->default('task')->after('title');
            $table->string('priority')->default('medium')->after('status');
            $table->string('label')->nullable()->after('priority');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete()->after('label');
            $table->foreignId('sprint_id')->nullable()->after('department_id');
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->nullOnDelete()->after('sprint_id');
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete()->after('parent_task_id');
            $table->date('due_date')->nullable()->after('reporter_id');
            $table->decimal('estimated_hours', 5, 2)->nullable()->after('due_date');
            $table->decimal('actual_hours', 5, 2)->nullable()->after('estimated_hours');
            $table->json('watchers')->nullable()->after('actual_hours');
            $table->json('attachments')->nullable()->after('watchers');
            $table->integer('order_index')->default(0)->after('attachments');
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete()->after('order_index');
            $table->timestamp('archived_at')->nullable()->after('completed_by');
        });
    }
    public function down(): void {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['ticket_number','type','priority','label','department_id','sprint_id','parent_task_id','reporter_id','due_date','estimated_hours','actual_hours','watchers','attachments','order_index','completed_by','archived_at']);
        });
    }
};
