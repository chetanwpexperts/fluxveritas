<?php

namespace Database\Seeders;

use App\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeProfileSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = [
            [
                'email'           => 'manager@techcorp.com',
                'designation'     => 'Engineering Manager',
                'phone'           => '+91-9876543210',
                'date_of_joining' => '2022-01-15',
                'skills'          => ['PHP', 'Laravel', 'Team Leadership', 'System Design'],
                'bio'             => 'Engineering Manager with 8+ years building scalable systems.',
                'github_username' => 'rahulsharma',
            ],
            [
                'email'           => 'teamlead@techcorp.com',
                'designation'     => 'Senior Developer',
                'phone'           => '+91-9123456780',
                'date_of_joining' => '2022-06-01',
                'skills'          => ['React', 'Vue', 'JavaScript', 'Node.js'],
                'bio'             => 'Frontend specialist passionate about clean UI.',
                'github_username' => 'priyapatel',
            ],
            [
                'email'           => 'alex@techcorp.com',
                'designation'     => 'Mid Frontend Developer',
                'phone'           => '+91-9234567891',
                'date_of_joining' => '2023-03-10',
                'skills'          => ['React', 'CSS', 'TypeScript'],
                'github_username' => 'alexkumar',
            ],
            [
                'email'           => 'sarah@techcorp.com',
                'designation'     => 'Senior Backend Developer',
                'phone'           => '+91-9345678902',
                'date_of_joining' => '2023-07-20',
                'skills'          => ['PHP', 'MySQL', 'REST APIs', 'Redis'],
                'github_username' => 'sarahsingh',
            ],
            [
                'email'           => 'neha@techcorp.com',
                'designation'     => 'HR Executive',
                'phone'           => '+91-9456789013',
                'date_of_joining' => '2023-01-05',
                'skills'          => ['Recruitment', 'HR Policy', 'Employee Relations', 'Payroll'],
                'bio'             => 'HR Executive managing people operations.',
                'is_directory_visible' => true,
            ],
        ];

        foreach ($profiles as $data) {
            $user = User::where('email', $data['email'])->first();
            if (!$user) {
                $this->command->warn("User not found: {$data['email']}");
                continue;
            }

            EmployeeProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'designation'          => $data['designation'],
                    'phone'                => $data['phone'] ?? null,
                    'date_of_joining'      => $data['date_of_joining'] ?? null,
                    'skills'               => $data['skills'] ?? [],
                    'bio'                  => $data['bio'] ?? null,
                    'github_username'      => $data['github_username'] ?? null,
                    'is_directory_visible' => $data['is_directory_visible'] ?? true,
                ]
            );

            $this->command->info("Profile seeded for {$user->name} ({$data['email']})");
        }
    }
}
