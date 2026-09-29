<?php

namespace App\Services\Outy\Tools;

class RejectLeave extends ReviewLeaveTool
{
    public function name(): string
    {
        return 'reject_leave';
    }

    public function description(): string
    {
        return 'Prepare rejection of a pending leave request (confirm card; nothing changes until the user confirms). Get the request_id from get_pending_leave_requests; add a short note explaining why.';
    }

    protected function approves(): bool
    {
        return false;
    }
}
