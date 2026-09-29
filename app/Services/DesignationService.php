<?php

namespace App\Services;

class DesignationService
{
    public static function getWorkLogCategories(?string $designation): array
    {
        $d = strtolower($designation ?? '');

        if (self::contains($d, ['developer', 'devops', 'architect', 'tech', 'engineer', 'dba', 'database'])) {
            return [
                'Development', 'Code Review', 'Bug Fix', 'Testing', 'Deployment',
                'Research', 'Documentation', 'Meeting', 'Planning', 'Refactoring',
                'Support', 'Training', 'Other',
            ];
        }

        if (self::contains($d, ['qa', 'quality', 'tester', 'testing', 'automation'])) {
            return [
                'Manual Testing', 'Automation Testing', 'Bug Reporting', 'Test Case Writing',
                'Regression Testing', 'Performance Testing', 'UAT Support',
                'Documentation', 'Meeting', 'Research', 'Training', 'Other',
            ];
        }

        if (self::contains($d, ['design', 'designer', 'ui', 'ux', 'graphic', 'creative'])) {
            return [
                'UI Design', 'UX Research', 'Wireframing', 'Prototyping', 'Design Review',
                'Asset Creation', 'Branding', 'Client Feedback', 'Meeting',
                'Documentation', 'Training', 'Other',
            ];
        }

        if (self::contains($d, ['product', 'business analyst', 'ba', 'scrum', 'project manager', 'pm'])) {
            return [
                'Requirements Gathering', 'Documentation', 'Client Meeting', 'Analysis',
                'UAT', 'Sprint Planning', 'Retrospective', 'Stakeholder Meeting',
                'Reporting', 'Process Mapping', 'Training', 'Other',
            ];
        }

        if (self::contains($d, ['hr', 'human resource', 'recruiter', 'recruitment', 'talent', 'people'])) {
            return [
                'Recruitment', 'Interview', 'Onboarding', 'Offboarding', 'Policy',
                'Training & Development', 'Employee Relations', 'Performance Review',
                'Payroll', 'Compliance', 'Meeting', 'Other',
            ];
        }

        if (self::contains($d, ['marketing', 'content', 'seo', 'social media', 'brand', 'growth'])) {
            return [
                'Content Creation', 'Social Media', 'SEO', 'Campaign Management',
                'Analytics', 'Email Marketing', 'Meeting', 'Research',
                'Reporting', 'Design Review', 'Other',
            ];
        }

        return [
            'Meeting', 'Development', 'Code Review', 'Research', 'Support', 'Training',
            'Travel', 'Client Call', 'Vendor Call', 'Planning', 'Documentation',
            'Design', 'Testing', 'Reporting', 'Recruitment', 'Other',
        ];
    }

    public static function getDesignationLabel(?string $designation, ?string $seniority): string
    {
        if (!$designation) return 'Team Member';

        $parts = array_filter([
            $seniority ? ucfirst($seniority) : null,
            ucwords(str_replace('-', ' ', $designation)),
        ]);

        return implode(' ', $parts);
    }

    public static function requiresGithub(?string $designation): bool
    {
        return self::contains(
            strtolower($designation ?? ''),
            ['developer', 'devops', 'architect', 'tech-lead', 'automation', 'engineer']
        );
    }

    private static function contains(string $str, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($str, $needle)) return true;
        }
        return false;
    }
}
