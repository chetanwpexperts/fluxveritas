<?php

namespace App\Services\Outy\Tools;

use App\Exceptions\WorkflowException;
use App\Models\User;
use App\Services\LeaveService;

/** Shared by approve_leave and reject_leave. */
abstract class ReviewLeaveTool extends ActionTool
{
    protected array $roles = ['owner', 'admin', 'hr', 'team_lead', 'ceo', 'manager'];

    protected ?string $module = 'leave_management';

    abstract protected function approves(): bool;

    public function parameters(): array
    {
        return [
            'type'                 => 'object',
            'properties'           => [
                'request_id' => ['type' => 'integer', 'description' => 'request_id from get_pending_leave_requests'],
                'note'       => ['type' => 'string', 'description' => 'Optional note to the employee (max 300 characters)'],
            ],
            'required'             => ['request_id'],
            'additionalProperties' => false,
        ];
    }

    protected function prepare(User $user, array $args): array
    {
        $application = $this->pendingRequest($user, (int) ($args['request_id'] ?? 0));
        $note        = trim((string) ($args['note'] ?? ''));
        $verb        = $this->approves() ? 'Approve' : 'Reject';

        return [
            'title'   => "{$verb} {$application->user->name}'s {$application->leaveType->name}",
            'details' => array_values(array_filter([
                ['Dates', $application->from_date->format('D j M') . ' – ' . $application->to_date->format('D j M Y')],
                ['Days', (string) (float) $application->days],
                ['Reason', (string) $application->reason],
                $note !== '' ? ['Your note', mb_substr($note, 0, 300)] : null,
            ])),
            'payload' => ['request_id' => $application->id, 'note' => $note !== '' ? mb_substr($note, 0, 300) : null],
        ];
    }

    public function execute(User $user, array $payload): string
    {
        $service     = app(LeaveService::class);
        $application = $this->pendingRequest($user, (int) $payload['request_id']);
        $service->review($user, $application, $this->approves(), $payload['note'] ?? null);

        return ($this->approves() ? 'Approved' : 'Rejected') . " {$application->user->name}'s {$application->leaveType->name} ("
            . $application->from_date->format('j M') . ' – ' . $application->to_date->format('j M') . '). They have been notified.';
    }

    private function pendingRequest(User $user, int $id)
    {
        $application = app(LeaveService::class)->findApprovable($user, $id);

        if (!$application) {
            throw new WorkflowException('I can\'t find a leave request with that ID that you are allowed to review.');
        }
        if ($application->status !== 'pending') {
            throw new WorkflowException('That leave request has already been ' . $application->status . '.');
        }

        return $application;
    }
}
