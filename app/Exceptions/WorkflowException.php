<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A business rule stopped an action (insufficient leave balance, not allowed
 * to approve, …). The message is safe to show to the user; $field is the form
 * field it relates to, for screens that show errors next to inputs.
 */
class WorkflowException extends RuntimeException
{
    public function __construct(string $message, public readonly string $field = 'error')
    {
        parent::__construct($message);
    }
}
