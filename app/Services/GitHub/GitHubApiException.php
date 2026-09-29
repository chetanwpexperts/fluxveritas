<?php

namespace App\Services\GitHub;

use RuntimeException;

/** A GitHub API call failed. $kind: auth | not_found | rate_limited | error */
class GitHubApiException extends RuntimeException
{
    public function __construct(string $message, public readonly string $kind = 'error')
    {
        parent::__construct($message);
    }
}
