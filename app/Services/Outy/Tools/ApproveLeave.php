<?php

namespace App\Services\Outy\Tools;

class ApproveLeave extends ReviewLeaveTool
{
    public function name(): string
    {
        return 'approve_leave';
    }

    public function description(): string
    {
        return 'Prepare approval of a pending leave request (confirm card; nothing changes until the user confirms). Get the request_id from get_pending_leave_requests.';
    }

    protected function approves(): bool
    {
        return true;
    }
}
