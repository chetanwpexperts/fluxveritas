<?php

namespace App\Services\Import;

use App\Models\LeaveType;

/**
 * Our import fields, the column names HR systems commonly use for them, and
 * automatic column matching.
 *
 * Preset column names are the ones commonly seen in each product's employee
 * export. Exports vary by account and version, so the mapping screen always
 * lets the user correct a match.
 */
class ImportPresets
{
    public const PRESETS = [
        'custom'    => 'Custom / other',
        'outraqhq'  => 'OutraqHQ template',
        'keka'      => 'Keka',
        'zoho'      => 'Zoho People',
        'greythr'   => 'greytHR',
        'darwinbox' => 'Darwinbox',
        'bamboohr'  => 'BambooHR',
    ];

    /** field => [label, required?] */
    public const FIELDS = [
        'full_name'               => ['Full name', false],
        'first_name'              => ['First name', false],
        'last_name'               => ['Last name', false],
        'email'                   => ['Work email', true],
        'role'                    => ['Role in OutraqHQ', false],
        'department'              => ['Department', false],
        'team'                    => ['Team', false],
        'designation'             => ['Designation / job title', false],
        'reporting_manager_email' => ['Reporting manager email', false],
        'reporting_manager_name'  => ['Reporting manager name', false],
        'join_date'               => ['Date of joining', false],
        'phone'                   => ['Phone', false],
        'employment_type'         => ['Employment type', false],
        'work_location'           => ['Work location', false],
        'status'                  => ['Employee status (active / exited)', false],
    ];

    /** Names any system might use, per field. */
    private const COMMON = [
        'full_name'               => ['name', 'full name', 'employee name', 'display name', 'emp name'],
        'first_name'              => ['first name', 'firstname', 'given name'],
        'last_name'               => ['last name', 'lastname', 'surname', 'family name'],
        'email'                   => ['email', 'work email', 'official email', 'company email', 'email address', 'email id', 'official email id', 'company email id', 'e mail'],
        'role'                    => ['role', 'outraqhq role', 'access role', 'app role'],
        'department'              => ['department', 'dept', 'department name'],
        'team'                    => ['team', 'team name', 'sub department', 'sub department name'],
        'designation'             => ['designation', 'job title', 'title', 'position', 'job role'],
        'reporting_manager_email' => ['reporting manager email', 'manager email', 'reporting to email', 'supervisor email', 'reporting manager email id', 'direct manager email', 'reports to email'],
        'reporting_manager_name'  => ['reporting manager', 'reporting to', 'manager', 'manager name', 'reports to', 'supervisor', 'direct manager'],
        'join_date'               => ['date of joining', 'joining date', 'join date', 'doj', 'hire date', 'date joined', 'start date'],
        'phone'                   => ['phone', 'mobile', 'mobile number', 'mobile phone', 'phone number', 'contact number', 'work phone', 'mobile no'],
        'employment_type'         => ['employment type', 'employee type', 'worker type', 'type of employment'],
        'work_location'           => ['location', 'work location', 'office location', 'office', 'base location'],
        'status'                  => ['status', 'employee status', 'employment status'],
    ];

    /** Extra names typical of each product's export. */
    private const PRESET_NAMES = [
        'outraqhq'  => [
            'full_name' => ['name'], 'email' => ['email'], 'reporting_manager_email' => ['reporting manager email'],
            'join_date' => ['join date'], 'employment_type' => ['employment type'],
        ],
        'keka'      => [
            'full_name' => ['display name'], 'email' => ['work email'], 'designation' => ['job title'],
            'reporting_manager_name' => ['reporting to'], 'reporting_manager_email' => ['reporting manager email'],
            'join_date' => ['date of joining'], 'employment_type' => ['worker type'], 'status' => ['employment status'],
            'phone' => ['mobile phone'], 'team' => ['sub department'],
        ],
        'zoho'      => [
            'email' => ['email address', 'email id'], 'reporting_manager_name' => ['reporting to'],
            'reporting_manager_email' => ['reporting to email'], 'join_date' => ['date of joining'],
            'employment_type' => ['employee type'], 'status' => ['employee status'], 'phone' => ['mobile phone', 'work phone'],
        ],
        'greythr'   => [
            'full_name' => ['employee name'], 'email' => ['email', 'official email'], 'reporting_manager_name' => ['reporting manager'],
            'reporting_manager_email' => ['reporting manager email'], 'join_date' => ['date of joining', 'doj'],
            'phone' => ['mobile number'], 'status' => ['status'],
        ],
        'darwinbox' => [
            'full_name' => ['full name'], 'email' => ['company email id', 'official email id'],
            'reporting_manager_email' => ['direct manager email', 'reporting manager email id'], 'reporting_manager_name' => ['direct manager'],
            'join_date' => ['date of joining'], 'employment_type' => ['employee type'], 'status' => ['employment status'],
            'work_location' => ['office location'], 'phone' => ['mobile no'],
        ],
        'bamboohr'  => [
            'email' => ['work email'], 'designation' => ['job title'], 'reporting_manager_name' => ['reports to'],
            'reporting_manager_email' => ['supervisor email'], 'join_date' => ['hire date'], 'employment_type' => ['employment status'],
            'status' => ['status'], 'phone' => ['mobile phone', 'work phone'],
        ],
    ];

    /** @return array<string, string> field => label, including "leave:{code}" fields for the organization's leave types */
    public static function fieldLabels(int $organizationId): array
    {
        $labels = array_map(fn ($f) => $f[0], self::FIELDS);

        foreach (self::leaveTypes($organizationId) as $code => $name) {
            $labels["leave:{$code}"] = "Leave balance: {$name}";
        }

        return $labels;
    }

    /** @return array<string, string> code => name */
    public static function leaveTypes(int $organizationId): array
    {
        return LeaveType::where('organization_id', $organizationId)->where('is_active', true)->orderBy('name')->pluck('name', 'code')->all();
    }

    /**
     * Suggested field per column index. Each field is used once; unmatched columns map to null (ignored).
     *
     * @return array<int, array{field: ?string, confidence: string}>
     */
    public static function suggest(array $headers, string $preset, int $organizationId): array
    {
        $names  = self::namesFor($preset, $organizationId);
        $result = array_fill_keys(array_keys($headers), ['field' => null, 'confidence' => 'none']);
        $taken  = [];

        // Pass 1: exact names (preset names first), pass 2: close spelling
        foreach (['exact', 'close'] as $pass) {
            foreach ($headers as $i => $header) {
                if ($result[$i]['field']) {
                    continue;
                }
                $h = self::normalize($header);
                foreach ($names as $field => $candidates) {
                    if (isset($taken[$field])) {
                        continue;
                    }
                    foreach ($candidates as $candidate) {
                        $hit = $pass === 'exact' ? $h === $candidate : self::close($h, $candidate);
                        if ($hit) {
                            $result[$i] = ['field' => $field, 'confidence' => $pass === 'exact' ? 'high' : 'medium'];
                            $taken[$field] = true;
                            continue 3;
                        }
                    }
                }
            }
        }

        return $result;
    }

    public static function normalize(string $header): string
    {
        $h = mb_strtolower(trim($header));
        $h = str_replace(['#', '&'], [' number ', ' and '], $h);
        $h = preg_replace('/[^a-z0-9]+/', ' ', $h);

        return trim(preg_replace('/\s+/', ' ', $h));
    }

    /** field => candidate names, preset-specific names first. */
    private static function namesFor(string $preset, int $organizationId): array
    {
        $names = [];
        foreach (self::COMMON as $field => $common) {
            $names[$field] = array_values(array_unique(array_merge(self::PRESET_NAMES[$preset][$field] ?? [], $common)));
        }

        foreach (self::leaveTypes($organizationId) as $code => $name) {
            $n = self::normalize($name);
            $c = self::normalize($code);
            $names["leave:{$code}"] = ["{$n} balance", "{$c} balance", "{$n} available", "{$c} available", "balance {$n}", "balance {$c}"];
        }

        // Preset-specific fields are tried before generic ones
        uksort($names, fn ($a, $b) => (int) !isset(self::PRESET_NAMES[$preset][$a]) <=> (int) !isset(self::PRESET_NAMES[$preset][$b]));

        return $names;
    }

    private static function close(string $a, string $b): bool
    {
        if ($a === '' || $b === '' || abs(strlen($a) - strlen($b)) > 3) {
            return false;
        }

        return levenshtein($a, $b) <= max(1, (int) floor(strlen($b) / 6));
    }
}
