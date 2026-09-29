<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        $designations = [
            // ── ENGINEERING ──────────────────────────────────────────────────
            ['category' => 'engineering', 'title' => 'Junior Backend Developer',   'seniority_level' => 'junior',    'requires_github' => true,  'sort_order' => 10],
            ['category' => 'engineering', 'title' => 'Mid Backend Developer',       'seniority_level' => 'mid',       'requires_github' => true,  'sort_order' => 11],
            ['category' => 'engineering', 'title' => 'Senior Backend Developer',    'seniority_level' => 'senior',    'requires_github' => true,  'sort_order' => 12],
            ['category' => 'engineering', 'title' => 'Lead Backend Developer',      'seniority_level' => 'lead',      'requires_github' => true,  'sort_order' => 13],
            ['category' => 'engineering', 'title' => 'Principal Backend Developer', 'seniority_level' => 'principal', 'requires_github' => true,  'sort_order' => 14],
            ['category' => 'engineering', 'title' => 'Junior Frontend Developer',   'seniority_level' => 'junior',    'requires_github' => true,  'sort_order' => 20],
            ['category' => 'engineering', 'title' => 'Mid Frontend Developer',      'seniority_level' => 'mid',       'requires_github' => true,  'sort_order' => 21],
            ['category' => 'engineering', 'title' => 'Senior Frontend Developer',   'seniority_level' => 'senior',    'requires_github' => true,  'sort_order' => 22],
            ['category' => 'engineering', 'title' => 'Lead Frontend Developer',     'seniority_level' => 'lead',      'requires_github' => true,  'sort_order' => 23],
            ['category' => 'engineering', 'title' => 'Junior Full Stack Developer', 'seniority_level' => 'junior',    'requires_github' => true,  'sort_order' => 30],
            ['category' => 'engineering', 'title' => 'Mid Full Stack Developer',    'seniority_level' => 'mid',       'requires_github' => true,  'sort_order' => 31],
            ['category' => 'engineering', 'title' => 'Senior Full Stack Developer', 'seniority_level' => 'senior',    'requires_github' => true,  'sort_order' => 32],
            ['category' => 'engineering', 'title' => 'Lead Full Stack Developer',   'seniority_level' => 'lead',      'requires_github' => true,  'sort_order' => 33],
            ['category' => 'engineering', 'title' => 'Junior Mobile Developer',     'seniority_level' => 'junior',    'requires_github' => true,  'sort_order' => 40],
            ['category' => 'engineering', 'title' => 'Senior Mobile Developer',     'seniority_level' => 'senior',    'requires_github' => true,  'sort_order' => 41],
            ['category' => 'engineering', 'title' => 'DevOps Engineer',             'seniority_level' => 'mid',       'requires_github' => true,  'sort_order' => 50],
            ['category' => 'engineering', 'title' => 'Senior DevOps Engineer',      'seniority_level' => 'senior',    'requires_github' => true,  'sort_order' => 51],
            ['category' => 'engineering', 'title' => 'Database Administrator',      'seniority_level' => 'mid',       'requires_github' => false, 'sort_order' => 60],
            ['category' => 'engineering', 'title' => 'Senior DBA',                  'seniority_level' => 'senior',    'requires_github' => false, 'sort_order' => 61],
            ['category' => 'engineering', 'title' => 'Software Architect',          'seniority_level' => 'principal', 'requires_github' => true,  'sort_order' => 70],
            ['category' => 'engineering', 'title' => 'Tech Lead',                   'seniority_level' => 'lead',      'requires_github' => true,  'sort_order' => 71],

            // ── QA ───────────────────────────────────────────────────────────
            ['category' => 'qa', 'title' => 'Junior QA Engineer',         'seniority_level' => 'junior', 'requires_github' => false, 'sort_order' => 10],
            ['category' => 'qa', 'title' => 'QA Engineer',                'seniority_level' => 'mid',    'requires_github' => false, 'sort_order' => 11],
            ['category' => 'qa', 'title' => 'Senior QA Engineer',         'seniority_level' => 'senior', 'requires_github' => false, 'sort_order' => 12],
            ['category' => 'qa', 'title' => 'QA Lead',                    'seniority_level' => 'lead',   'requires_github' => false, 'sort_order' => 13],
            ['category' => 'qa', 'title' => 'Junior Automation Engineer',  'seniority_level' => 'junior', 'requires_github' => true,  'sort_order' => 20],
            ['category' => 'qa', 'title' => 'Automation Engineer',         'seniority_level' => 'mid',    'requires_github' => true,  'sort_order' => 21],
            ['category' => 'qa', 'title' => 'Senior Automation Engineer',  'seniority_level' => 'senior', 'requires_github' => true,  'sort_order' => 22],
            ['category' => 'qa', 'title' => 'Performance Test Engineer',   'seniority_level' => 'mid',    'requires_github' => false, 'sort_order' => 30],
            ['category' => 'qa', 'title' => 'QA Manager',                  'seniority_level' => 'manager','requires_github' => false, 'sort_order' => 40],

            // ── DESIGN ───────────────────────────────────────────────────────
            ['category' => 'design', 'title' => 'Junior UI Designer',    'seniority_level' => 'junior', 'requires_github' => false, 'sort_order' => 10],
            ['category' => 'design', 'title' => 'UI Designer',           'seniority_level' => 'mid',    'requires_github' => false, 'sort_order' => 11],
            ['category' => 'design', 'title' => 'Senior UI Designer',    'seniority_level' => 'senior', 'requires_github' => false, 'sort_order' => 12],
            ['category' => 'design', 'title' => 'Junior UX Designer',    'seniority_level' => 'junior', 'requires_github' => false, 'sort_order' => 20],
            ['category' => 'design', 'title' => 'UX Designer',           'seniority_level' => 'mid',    'requires_github' => false, 'sort_order' => 21],
            ['category' => 'design', 'title' => 'Senior UX Designer',    'seniority_level' => 'senior', 'requires_github' => false, 'sort_order' => 22],
            ['category' => 'design', 'title' => 'Product Designer',      'seniority_level' => 'mid',    'requires_github' => false, 'sort_order' => 30],
            ['category' => 'design', 'title' => 'Lead Product Designer',  'seniority_level' => 'lead',   'requires_github' => false, 'sort_order' => 31],
            ['category' => 'design', 'title' => 'Graphic Designer',      'seniority_level' => 'mid',    'requires_github' => false, 'sort_order' => 40],
            ['category' => 'design', 'title' => 'UX Researcher',         'seniority_level' => 'mid',    'requires_github' => false, 'sort_order' => 50],
            ['category' => 'design', 'title' => 'Design Lead',           'seniority_level' => 'lead',   'requires_github' => false, 'sort_order' => 60],

            // ── PRODUCT / MANAGEMENT ─────────────────────────────────────────
            ['category' => 'product', 'title' => 'Junior Business Analyst',  'seniority_level' => 'junior',  'requires_github' => false, 'sort_order' => 10],
            ['category' => 'product', 'title' => 'Business Analyst',         'seniority_level' => 'mid',     'requires_github' => false, 'sort_order' => 11],
            ['category' => 'product', 'title' => 'Senior Business Analyst',  'seniority_level' => 'senior',  'requires_github' => false, 'sort_order' => 12],
            ['category' => 'product', 'title' => 'Junior Project Manager',   'seniority_level' => 'junior',  'requires_github' => false, 'sort_order' => 20],
            ['category' => 'product', 'title' => 'Project Manager',          'seniority_level' => 'mid',     'requires_github' => false, 'sort_order' => 21],
            ['category' => 'product', 'title' => 'Senior Project Manager',   'seniority_level' => 'senior',  'requires_github' => false, 'sort_order' => 22],
            ['category' => 'product', 'title' => 'Product Manager',          'seniority_level' => 'mid',     'requires_github' => false, 'sort_order' => 30],
            ['category' => 'product', 'title' => 'Senior Product Manager',   'seniority_level' => 'senior',  'requires_github' => false, 'sort_order' => 31],
            ['category' => 'product', 'title' => 'Product Owner',            'seniority_level' => 'senior',  'requires_github' => false, 'sort_order' => 32],
            ['category' => 'product', 'title' => 'Scrum Master',             'seniority_level' => 'mid',     'requires_github' => false, 'sort_order' => 40],
            ['category' => 'product', 'title' => 'Engineering Manager',      'seniority_level' => 'manager', 'requires_github' => false, 'sort_order' => 50],
            ['category' => 'product', 'title' => 'VP Engineering',           'seniority_level' => 'director','requires_github' => false, 'sort_order' => 60],
            ['category' => 'product', 'title' => 'CTO',                      'seniority_level' => 'c_level', 'requires_github' => false, 'sort_order' => 70],

            // ── HR ───────────────────────────────────────────────────────────
            ['category' => 'hr', 'title' => 'HR Executive',         'seniority_level' => 'junior',  'requires_github' => false, 'sort_order' => 10],
            ['category' => 'hr', 'title' => 'Senior HR Executive',  'seniority_level' => 'mid',     'requires_github' => false, 'sort_order' => 11],
            ['category' => 'hr', 'title' => 'HR Manager',           'seniority_level' => 'manager', 'requires_github' => false, 'sort_order' => 20],
            ['category' => 'hr', 'title' => 'Recruiter',            'seniority_level' => 'mid',     'requires_github' => false, 'sort_order' => 30],
            ['category' => 'hr', 'title' => 'Senior Recruiter',     'seniority_level' => 'senior',  'requires_github' => false, 'sort_order' => 31],
            ['category' => 'hr', 'title' => 'L&D Specialist',       'seniority_level' => 'mid',     'requires_github' => false, 'sort_order' => 40],
            ['category' => 'hr', 'title' => 'HR Business Partner',  'seniority_level' => 'senior',  'requires_github' => false, 'sort_order' => 50],
            ['category' => 'hr', 'title' => 'HR Director',          'seniority_level' => 'director','requires_github' => false, 'sort_order' => 60],

            // ── MARKETING ────────────────────────────────────────────────────
            ['category' => 'marketing', 'title' => 'Marketing Executive',         'seniority_level' => 'junior', 'requires_github' => false, 'sort_order' => 10],
            ['category' => 'marketing', 'title' => 'Senior Marketing Executive',  'seniority_level' => 'mid',    'requires_github' => false, 'sort_order' => 11],
            ['category' => 'marketing', 'title' => 'Digital Marketing Manager',   'seniority_level' => 'manager','requires_github' => false, 'sort_order' => 20],
            ['category' => 'marketing', 'title' => 'Content Writer',              'seniority_level' => 'mid',    'requires_github' => false, 'sort_order' => 30],
            ['category' => 'marketing', 'title' => 'SEO Specialist',              'seniority_level' => 'mid',    'requires_github' => false, 'sort_order' => 40],
            ['category' => 'marketing', 'title' => 'Marketing Manager',           'seniority_level' => 'manager','requires_github' => false, 'sort_order' => 50],
        ];

        foreach ($designations as $data) {
            $slug = Str::slug($data['title']);
            Designation::updateOrCreate(
                ['organization_id' => null, 'slug' => $slug],
                array_merge($data, ['slug' => $slug, 'organization_id' => null, 'is_template' => true, 'is_active' => true])
            );
        }
    }
}
