<?php

namespace Tests\Unit;

use App\Models\IncrementCriteria;
use App\Models\IncrementPolicy;
use App\Models\Organization;
use App\Models\User;
use App\Services\IncrementCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncrementCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_score_calculation_with_attendance_normalization(): void
    {
        $org = Organization::create([
            'name'           => 'Test Corp',
            'slug'           => 'test-corp',
            'status'         => 'active',
            'plan'           => 'pro',
            'billing_status' => 'active',
        ]);

        $user = User::factory()->create([
            'organization_id' => $org->id,
            'name'            => 'John Developer',
            'email'           => 'john@testcorp.com',
            'designation'     => 'Senior Full Stack Developer',
            'seniority_level' => 'senior',
            'is_active'       => true,
        ]);

        $policy = IncrementPolicy::create([
            'organization_id'     => $org->id,
            'name'                => 'Standard Policy',
            'created_by'          => $user->id,
            'effective_year'      => 2026,
            'is_active'           => true,
            'anti_gaming_enabled' => true,
        ]);

        IncrementCriteria::create([
            'organization_id' => $org->id,
            'policy_id'       => $policy->id,
            'criteria_name'   => 'attendance',
            'criteria_label'  => 'Attendance Rate',
            'weight_percent'  => 30.0,
            'criteria_type'   => 'attendance',
            'data_source'     => 'attendance',
            'is_active'       => true,
        ]);

        IncrementCriteria::create([
            'organization_id' => $org->id,
            'policy_id'       => $policy->id,
            'criteria_name'   => 'work_logs',
            'criteria_label'  => 'Work Logging Consistency',
            'weight_percent'  => 70.0,
            'criteria_type'   => 'logging',
            'data_source'     => 'work_logs',
            'is_active'       => true,
        ]);

        $calculator = new IncrementCalculator($policy);
        $month = Carbon::create(2026, 8, 1);
        $scoreRecord = $calculator->calculateMonthlyScore($user, $month);

        $this->assertNotNull($scoreRecord);
        $this->assertEquals($user->id, $scoreRecord->user_id);
        $this->assertGreaterThanOrEqual(0, $scoreRecord->final_score);
        $this->assertLessThanOrEqual(100, $scoreRecord->final_score);
    }
}
