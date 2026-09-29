<?php

namespace App\Services\Outy;

use RuntimeException;

/** A tool was requested by a user who may not use it. */
class ToolDeniedException extends RuntimeException
{
}
