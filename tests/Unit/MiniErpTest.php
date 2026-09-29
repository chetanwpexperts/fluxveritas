<?php

namespace Tests\Unit;

use App\Models\CompanyAsset;
use App\Models\Expense;
use App\Models\Organization;
use App\Models\Payroll;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiniErpTest extends TestCase
{
    use RefreshDatabase;

    public function test_mini_erp_models_and_relationships(): void
    {
        $org = Organization::create([
            'name'           => 'ERP Corp',
            'slug'           => 'erp-corp',
            'status'         => 'active',
            'plan'           => 'pro',
            'billing_status' => 'active',
        ]);

        $user = User::factory()->create([
            'organization_id' => $org->id,
            'name'            => 'Sarah Manager',
            'email'           => 'sarah@erp.com',
            'is_active'       => true,
        ]);

        // 1. Test Payroll
        $payroll = Payroll::create([
            'organization_id' => $org->id,
            'user_id'         => $user->id,
            'base_salary'     => 60000.00,
            'increment_pct'   => 10.00,
            'final_salary'    => 66000.00,
            'month_year'      => '2026-08',
            'status'          => 'processed',
        ]);
        $this->assertEquals(66000.00, $payroll->final_salary);

        // 2. Test Expense
        $expense = Expense::create([
            'organization_id' => $org->id,
            'user_id'         => $user->id,
            'title'           => 'Client Dinner',
            'amount'          => 2500.00,
            'category'        => 'Client',
            'status'          => 'pending',
        ]);
        $this->assertEquals(2500.00, $expense->amount);

        // 3. Test Asset Vault
        $asset = CompanyAsset::create([
            'organization_id' => $org->id,
            'assigned_to'     => $user->id,
            'asset_name'      => 'MacBook Pro M3',
            'category'        => 'Laptop',
            'serial_number'   => 'C02X90123',
            'status'          => 'assigned',
        ]);
        $this->assertEquals($user->id, $asset->assigned_to);

        // 4. Test Timesheets
        $timesheet = Timesheet::create([
            'organization_id' => $org->id,
            'user_id'         => $user->id,
            'work_date'       => now()->toDateString(),
            'clock_in'        => '09:00:00',
            'clock_out'       => '17:00:00',
            'total_hours'     => 8.00,
            'shift_type'      => 'General',
            'status'          => 'present',
        ]);
        $this->assertEquals(8.00, $timesheet->total_hours);
    }
}
