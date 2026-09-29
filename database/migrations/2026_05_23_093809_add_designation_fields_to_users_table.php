<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('job_title')->nullable()->after('department_id');
            $table->string('designation')->nullable()->after('job_title');
            $table->string('seniority_level')->nullable()->after('designation');
            $table->string('employment_type')->default('full_time')->after('seniority_level');
            $table->unsignedBigInteger('reporting_manager_id')->nullable()->after('employment_type');
            $table->foreign('reporting_manager_id')->references('id')->on('users')->nullOnDelete();
            $table->string('work_location')->default('onsite')->after('reporting_manager_id');
            $table->json('skills')->nullable()->after('work_location');
            $table->string('profile_photo')->nullable()->after('skills');
            $table->date('probation_end_date')->nullable()->after('profile_photo');
            $table->string('phone')->nullable()->after('probation_end_date');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['reporting_manager_id']);
            $table->dropColumn([
                'job_title', 'designation', 'seniority_level', 'employment_type',
                'reporting_manager_id', 'work_location', 'skills', 'profile_photo',
                'probation_end_date', 'phone',
            ]);
        });
    }
};
