<?php

namespace App\Services\Outy\Tools;

use App\Exceptions\WorkflowException;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveService;
use Carbon\Carbon;

class ApplyLeave extends ActionTool
{
    protected ?string $module = 'leave_management';

    public function name(): string
    {
        return 'apply_leave';
    }

    public function description(): string
    {
        return 'Prepare a leave application for the current user (shown as a confirm card; nothing is submitted until they confirm). '
            . 'Ask for the leave type, dates and reason first if the user has not given them.';
    }

    public function parameters(): array
    {
        return [
            'type'                 => 'object',
            'properties'           => [
                'leave_type' => ['type' => 'string', 'description' => 'Leave type name or code, e.g. "Casual Leave" or "CL"'],
                'from_date'  => ['type' => 'string', 'description' => 'First day, YYYY-MM-DD'],
                'to_date'    => ['type' => 'string', 'description' => 'Last day, YYYY-MM-DD (same as from_date for one day)'],
                'reason'     => ['type' => 'string', 'description' => 'Reason, at least 5 characters'],
                'half_day'   => ['type' => 'string', 'enum' => ['none', 'morning', 'afternoon'], 'description' => 'Half day (one date only)'],
            ],
            'required'             => ['leave_type', 'from_date', 'to_date', 'reason'],
            'additionalProperties' => false,
        ];
    }

    protected function prepare(User $user, array $args): array
    {
        $type    = $this->resolveType($user, (string) ($args['leave_type'] ?? ''));
        $from    = $this->date($args['from_date'] ?? null, 'from_date');
        $to      = $this->date($args['to_date'] ?? $from, 'to_date');
        $halfDay = in_array($args['half_day'] ?? 'none', ['none', 'morning', 'afternoon'], true) ? ($args['half_day'] ?? 'none') : 'none';
        $reason  = trim((string) ($args['reason'] ?? ''));
        if (mb_strlen($reason) < 5) {
            throw new WorkflowException('Please give a reason for the leave (at least 5 characters).', 'reason');
        }

        $check = app(LeaveService::class)->check($user, $type->id, $from, $to, $halfDay);
        $left  = $check['available'] - $check['days'];

        return [
            'title'   => "Apply for {$type->name}",
            'details' => [
                ['Dates', $this->dateRange($from, $to) . ($halfDay !== 'none' ? " ({$halfDay} half day)" : '')],
                ['Working days', $this->num($check['days'])],
                ['Reason', $reason],
                ['Balance after', $this->num($left) . ' days' . ($type->requires_approval ? ' (once approved)' : '')],
            ],
            'payload' => ['leave_type_id' => $type->id, 'from' => $from, 'to' => $to, 'reason' => $reason, 'half_day' => $halfDay],
        ];
    }

    public function execute(User $user, array $payload): string
    {
        $application = app(LeaveService::class)->apply(
            $user, (int) $payload['leave_type_id'], $payload['from'], $payload['to'], $payload['reason'], $payload['half_day'] ?? 'none'
        );

        return "Leave applied: {$application->leaveType->name}, " . $this->dateRange($payload['from'], $payload['to'])
            . ' (' . $this->num((float) $application->days) . ' days). '
            . ($application->status === 'pending' ? 'It is waiting for approval.' : 'It is approved.');
    }

    private function resolveType(User $user, string $name): LeaveType
    {
        $types  = LeaveType::where('organization_id', $user->organization_id)->where('is_active', true)->get();
        $needle = mb_strtolower(trim($name));

        $match = $types->first(fn ($t) => mb_strtolower($t->code) === $needle || mb_strtolower($t->name) === $needle)
            ?? $types->first(fn ($t) => $needle !== '' && str_contains(mb_strtolower($t->name), $needle));

        if (!$match) {
            $list = $types->map(fn ($t) => "{$t->name} ({$t->code})")->implode(', ');
            throw new WorkflowException($list ? "Unknown leave type. Available types: {$list}." : 'No leave types are set up in your organization yet.', 'leave_type_id');
        }

        return $match;
    }

    private function dateRange(string $from, string $to): string
    {
        $a = Carbon::parse($from);
        $b = Carbon::parse($to);

        return $a->equalTo($b) ? $a->format('D j M Y') : $a->format('D j M') . ' – ' . $b->format('D j M Y');
    }

    private function num(float $n): string
    {
        return fmod($n, 1.0) === 0.0 ? (string) (int) $n : (string) $n;
    }
}
