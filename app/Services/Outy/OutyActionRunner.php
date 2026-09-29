<?php

namespace App\Services\Outy;

use App\Exceptions\WorkflowException;
use App\Models\AuditLog;
use App\Models\OutyPendingAction;
use App\Models\User;
use App\Services\Outy\Tools\ActionTool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Runs or cancels a confirm card. Only the user who got the card can use it,
 * only once, only before it expires, and only if they are still allowed to.
 */
class OutyActionRunner
{
    public function __construct(private ToolRegistry $registry) {}

    /** @return array{ok: bool, message: string, status: int} */
    public function confirm(User $user, string $token): array
    {
        return DB::transaction(function () use ($user, $token) {
            $action = $this->find($user, $token);
            if (is_array($action)) {
                return $action;
            }

            $tool = $this->registry->all()->get($action->tool);
            if (!$tool instanceof ActionTool || !$tool->allows($user) || $action->organization_id !== $user->organization_id) {
                return $this->finish($user, $action, 'failed', "You're no longer allowed to do this.", 403);
            }

            try {
                $message = $tool->execute($user, $action->payload);
            } catch (WorkflowException $e) {
                return $this->finish($user, $action, 'failed', $e->getMessage(), 422);
            } catch (\Throwable $e) {
                Log::error('Outy action failed', ['action_id' => $action->id, 'tool' => $action->tool, 'error' => $e->getMessage()]);
                return $this->finish($user, $action, 'failed', 'Something went wrong — nothing was changed. Please try again from the page itself.', 500);
            }

            return $this->finish($user, $action, 'confirmed', $message, 200, true);
        });
    }

    /** @return array{ok: bool, message: string, status: int} */
    public function cancel(User $user, string $token): array
    {
        return DB::transaction(function () use ($user, $token) {
            $action = $this->find($user, $token);
            if (is_array($action)) {
                return $action;
            }

            return $this->finish($user, $action, 'cancelled', 'Cancelled — nothing was changed.', 200, true);
        });
    }

    /** The user's pending action, or an error result. */
    private function find(User $user, string $token): OutyPendingAction|array
    {
        $action = OutyPendingAction::where('token', $token)->where('user_id', $user->id)->lockForUpdate()->first();

        if (!$action) {
            return ['ok' => false, 'message' => 'This confirmation was not found.', 'status' => 404];
        }
        if ($action->status !== 'pending') {
            return ['ok' => false, 'message' => "This was already {$action->status}.", 'status' => 409];
        }
        if ($action->isExpired()) {
            return $this->finish($user, $action, 'expired', 'This confirmation has expired — nothing was changed. Ask Outy again.', 410);
        }

        return $action;
    }

    private function finish(User $user, OutyPendingAction $action, string $status, string $message, int $httpStatus, bool $ok = false): array
    {
        $action->update(['status' => $status, 'result' => $message, 'resolved_at' => now()]);

        AuditLog::create([
            'organization_id' => $action->organization_id,
            'user_id'         => $user->id,
            'action'          => "outy.action_{$status}",
            'entity_type'     => 'outy_action',
            'entity_id'       => $action->id,
            'new_values'      => ['tool' => $action->tool, 'title' => $action->summary['title'] ?? null, 'message' => $message],
            'ip_address'      => request()?->ip(),
            'user_agent'      => request()?->userAgent(),
        ]);

        return ['ok' => $ok, 'message' => $message, 'status' => $httpStatus];
    }
}
