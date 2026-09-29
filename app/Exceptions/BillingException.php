<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A billing action that cannot go ahead. The message is safe to show to the user.
 */
class BillingException extends RuntimeException
{
}
