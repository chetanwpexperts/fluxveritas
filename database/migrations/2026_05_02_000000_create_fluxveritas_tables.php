<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Organizations
        if (!Schema::hasTable('organizations')) {
            Schema::create('organizations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('plan')->default('free');
                $table->integer('max_employees')->default(10);
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }
        
        // Users - skip if exists (Laravel default)
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained();
                $table->string('email')->unique();
                $table->string('name');
                $table->string('role')->default('employee');
                $table->string('github_username')->nullable();
                $table->string('department')->nullable();
                $table->date('join_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        } else {
            // Add missing columns to existing users table
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'organization_id')) {
                    $table->foreignId('organization_id')->constrained();
                }
                if (!Schema::hasColumn('users', 'role')) {
                    $table->string('role')->default('employee');
                }
                if (!Schema::hasColumn('users', 'github_username')) {
                    $table->string('github_username')->nullable();
                }
                if (!Schema::hasColumn('users', 'department')) {
                    $table->string('department')->nullable();
                }
                if (!Schema::hasColumn('users', 'join_date')) {
                    $table->date('join_date')->nullable();
                }
                if (!Schema::hasColumn('users', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }
        
        // Projects
        if (!Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('github_repo')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }
        
        // Activities
        if (!Schema::hasTable('activities')) {
            Schema::create('activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained();
                $table->foreignId('project_id')->constrained();
                $table->string('source');
                $table->string('event_type');
                $table->string('external_id')->nullable();
                $table->json('metadata');
                $table->decimal('complexity_score', 5, 2)->nullable();
                $table->decimal('impact_score', 5, 2)->nullable();
                $table->timestamp('occurred_at');
                $table->timestamps();
                
                $table->index(['user_id', 'occurred_at']);
            });
        }
        
        // Tasks
        if (!Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained();
                $table->foreignId('assigned_to')->constrained('users');
                $table->foreignId('assigned_by')->constrained('users');
                $table->string('title');
                $table->text('description')->nullable();
                $table->tinyInteger('difficulty')->default(5);
                $table->tinyInteger('visibility_score')->default(5);
                $table->string('status')->default('pending');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }
        
        // Fairness Flags
        if (!Schema::hasTable('fairness_flags')) {
            Schema::create('fairness_flags', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained();
                $table->foreignId('flagged_user_id')->nullable()->constrained('users');
                $table->foreignId('flagged_by_user_id')->nullable()->constrained('users');
                $table->string('flag_type');
                $table->decimal('confidence_score', 5, 4);
                $table->tinyInteger('layer')->default(1);
                $table->string('status')->default('pending');
                $table->json('evidence');
                $table->text('employee_response')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users');
                $table->timestamp('reviewed_at')->nullable();
                $table->text('resolution')->nullable();
                $table->timestamps();
                
                $table->index(['organization_id', 'status']);
            });
        }
        
        // Audit Logs
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained();
                $table->foreignId('user_id')->nullable()->constrained();
                $table->string('action');
                $table->string('entity_type')->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('chain_hash');
                $table->timestamps();
                
                $table->index(['organization_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('fairness_flags');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('users');
        Schema::dropIfExists('organizations');
    }
};
