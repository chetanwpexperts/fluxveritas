<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AiAccessControl
{
    private array $sensitiveFields = [
        'email', 'phone', 'address', 'salary', 'ssn', 'password',
        'personal_notes', 'medical_info', 'family_info'
    ];

    /**
     * Main gate: Can this user access this data?
     */
    public function allowAccess(User $user, string $dataType, array $context = []): bool
    {
        $checks = [
            'is_authenticated' => auth()->check() && auth()->id() === $user->id,
            'has_permission' => $this->hasPermission($user, $dataType),
            'working_hours' => $this->isWorkingHours($user),
            'device_verified' => $this->isDeviceVerified($user),
            'not_bulk_request' => !$this->isBulkRequest($context),
            'rate_limit_ok' => $this->checkRateLimit($user, $dataType),
        ];

        $failed = array_keys(array_filter($checks, fn($v) => $v === false));

        if (!empty($failed)) {
            Log::warning('AI Access Denied', [
                'user_id' => $user->id,
                'data_type' => $dataType,
                'failed_checks' => $failed,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Sanitize data before sending to external AI
     */
    public function sanitizeForExternalAi(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $this->sensitiveFields)) {
                continue;
            }

            if (in_array($key, ['id', 'user_id', 'employee_id'])) {
                $sanitized['anonymized_' . $key] = hash('sha256', $value . config('app.key'));
                continue;
            }

            if (in_array($key, ['name', 'first_name', 'last_name'])) {
                $sanitized[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value) && count($value) > 10) {
                $sanitized[$key . '_aggregated'] = [
                    'count' => count($value),
                    'avg' => is_numeric(reset($value)) ? array_sum($value) / count($value) : null,
                ];
                continue;
            }

            $sanitized[$key] = $value;
        }

        $sanitized['_meta'] = [
            'sanitized_at' => now()->toIso8601String(),
            'sanitized_by' => 'fluxveritas_ai_access_control',
        ];

        return $sanitized;
    }

    /**
     * Verify external AI response doesn't contain leaked data
     */
    public function verifyExternalAiResponse(string $response): bool
    {
        $patterns = [
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/',
            '/\b\d{3}-\d{2}-\d{4}\b/',
            '/\b\d{10}\b/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $response)) {
                Log::error('External AI leaked PII');
                return false;
            }
        }

        return true;
    }

    private function hasPermission(User $user, string $dataType): bool
    {
        $permissions = Cache::remember("user_permissions:{$user->id}", 3600, function () use ($user) {
            return $user->permissions()->pluck('name')->toArray();
        });

        return in_array("ai.access.{$dataType}", $permissions) || in_array('ai.access.*', $permissions);
    }

    private function isWorkingHours(User $user): bool
    {
        $timezone = $user->timezone ?? 'UTC';
        $hour = now($timezone)->hour;
        $day = now($timezone)->dayOfWeek;

        return $day >= 1 && $day <= 5 && $hour >= 7 && $hour <= 22;
    }

    private function isDeviceVerified(User $user): bool
    {
        return session()->get('device_verified', false) === true;
    }

    private function isBulkRequest(array $context): bool
    {
        return ($context['limit'] ?? 0) > 100 || ($context['offset'] ?? 0) > 1000;
    }

    private function checkRateLimit(User $user, string $dataType): bool
    {
        $key = "ai_rate:{$user->id}:{$dataType}";
        $limit = match($dataType) {
            'sensitive' => 10,
            'aggregated' => 100,
            'public' => 1000,
            default => 50,
        };

        $current = Cache::increment($key);
        Cache::expire($key, 3600);

        return $current <= $limit;
    }
}
