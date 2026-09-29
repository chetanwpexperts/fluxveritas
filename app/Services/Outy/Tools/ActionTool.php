<?php

namespace App\Services\Outy\Tools;

use App\Exceptions\WorkflowException;
use App\Models\OutyPendingAction;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * A tool that changes data. It never acts directly: handle() validates the
 * request and stores a pending action, and the chat widget shows a confirm
 * card. execute() runs only when the user presses Confirm, and re-applies the
 * same rules (the shared services) at that moment.
 */
abstract class ActionTool extends OutyTool
{
    /**
     * Validates the request and describes it for the confirm card.
     *
     * @return array{title: string, details: array<int, array{0: string, 1: string}>, payload: array}
     * @throws WorkflowException when the request can't be done
     */
    abstract protected function prepare(User $user, array $args): array;

    /**
     * Performs a confirmed action. Must re-check everything, since data may have
     * changed since the card was shown. Returns a message for the user.
     *
     * @throws WorkflowException
     */
    abstract public function execute(User $user, array $payload): string;

    final protected function handle(User $user, array $args): array
    {
        try {
            $plan = $this->prepare($user, $args);
        } catch (WorkflowException $e) {
            return ['error' => $e->getMessage()];
        }

        $action = OutyPendingAction::create([
            'token'           => Str::random(48),
            'user_id'         => $user->id,
            'organization_id' => $user->organization_id,
            'tool'            => $this->name(),
            'payload'         => $plan['payload'],
            'summary'         => ['title' => $plan['title'], 'details' => $plan['details']],
            'status'          => 'pending',
            'expires_at'      => now()->addMinutes(OutyPendingAction::TTL_MINUTES),
        ]);

        return [
            'status'  => 'awaiting_confirmation',
            'title'   => $plan['title'],
            'details' => collect($plan['details'])->map(fn ($d) => "{$d[0]}: {$d[1]}")->all(),
            'note'    => 'Nothing has been done yet. A confirm card with these details is shown to the user — ask them to check it and press Confirm.',
            '_card'   => ['id' => $action->token, 'title' => $plan['title'], 'details' => $plan['details'], 'expires_in_minutes' => OutyPendingAction::TTL_MINUTES],
        ];
    }

    protected function date(?string $value, string $field): string
    {
        try {
            return \Carbon\Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            throw new WorkflowException('Please give the date as YYYY-MM-DD, for example ' . now()->addDay()->toDateString() . '.', $field);
        }
    }
}
