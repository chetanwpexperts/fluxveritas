<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. PAYROLLS
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('base_salary', 12, 2)->default(0.00);
            $table->decimal('increment_pct', 5, 2)->default(0.00);
            $table->decimal('final_salary', 12, 2)->default(0.00);
            $table->string('month_year', 20); // e.g. "2026-08"
            $table->enum('status', ['draft', 'processed', 'paid'])->default('processed');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // 2. EXPENSES
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->decimal('amount', 10, 2);
            $table->string('category')->default('General'); // Travel, Hardware, Software, Client
            $table->string('receipt_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // 3. COMPANY ASSETS
        Schema::create('company_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('asset_name'); // e.g. "MacBook Pro M3"
            $table->string('category')->default('Laptop'); // Laptop, Monitor, Phone, Badge
            $table->string('serial_number')->nullable();
            $table->string('asset_condition')->default('Good'); // New, Good, Damaged
            $table->enum('status', ['available', 'assigned', 'under_repair', 'retired'])->default('assigned');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
        });

        // 4. TIMESHEETS
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('work_date');
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->decimal('total_hours', 5, 2)->default(0.00);
            $table->string('shift_type')->default('General'); // General, Morning, Night, Remote
            $table->enum('status', ['present', 'late', 'half_day', 'absent'])->default('present');
            $table->timestamps();

            $table->unique(['user_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timesheets');
        Schema::dropIfExists('company_assets');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payrolls');
    }
};
