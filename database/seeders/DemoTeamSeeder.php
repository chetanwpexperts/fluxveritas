<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Team;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DemoTeamSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $orgId = 1; // TechCorp

        foreach (['manager', 'team_lead', 'employee'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        $rahul   = User::where('email', 'manager@techcorp.com')->first();
        $priya   = User::where('email', 'teamlead@techcorp.com')->first();
        $alex    = User::where('email', 'alex@techcorp.com')->first();
        $sarah   = User::where('email', 'sarah@techcorp.com')->first();
        $marcus  = User::where('email', 'dev.marcus@techcorp.com')->first();
        $devansh = User::where('email', 'chetanwpexperts@gmail.com')->first();

        if (!$rahul || !$priya) {
            $this->command->error('Rahul or Priya not found. Check emails.');
            return;
        }

        // Assign Spatie roles
        $rahul->syncRoles(['manager']);
        $priya->syncRoles(['team_lead']);
        foreach ([$alex, $sarah, $marcus, $devansh] as $emp) {
            if ($emp) $emp->syncRoles(['employee']);
        }

        // Sync legacy role column
        $rahul->update(['role' => 'manager', 'organization_id' => $orgId]);
        $priya->update(['role' => 'team_lead', 'organization_id' => $orgId]);

        // Create Team XYZ led by Priya
        $team = Team::updateOrCreate(
            ['organization_id' => $orgId, 'slug' => 'team-xyz'],
            [
                'department_id' => $priya->department_id ?? 1,
                'team_lead_id'  => $priya->id,
                'name'          => 'Team XYZ',
                'description'   => 'Demo team led by Priya, managed by Rahul',
                'is_active'     => true,
            ]
        );

        // Priya in team, reports to Rahul
        $priya->update([
            'team_id'              => $team->id,
            'reporting_manager_id' => $rahul->id,
        ]);

        // Employees in team, report to Priya
        foreach ([$alex, $sarah, $marcus, $devansh] as $emp) {
            if ($emp) {
                $emp->update([
                    'team_id'              => $team->id,
                    'reporting_manager_id' => $priya->id,
                    'organization_id'      => $orgId,
                    'role'                 => 'employee',
                ]);
            }
        }

        // Rahul oversees but is not in the team
        $rahul->update(['reporting_manager_id' => null]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('');
        $this->command->info('Demo Team XYZ created:');
        $this->command->info('  Manager    : Rahul Sharma  (manager@techcorp.com)');
        $this->command->info('  Team Lead  : Priya Patel   (teamlead@techcorp.com)');
        $this->command->info('  Members    : Alex, Sarah, Marcus, Devansh');
        $this->command->info('  Hierarchy  : Rahul → Priya → [Alex, Sarah, Marcus, Devansh]');
    }
}
