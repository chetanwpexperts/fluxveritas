<?php

namespace App\Services\Outy;

use RuntimeException;

/** The AI service could not produce an answer; the caller should fall back. */
class OutyUnavailableException extends RuntimeException
{
}
