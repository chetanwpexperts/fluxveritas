<?php

namespace App\Services\Outy;

/** Final answer from OutyAgent plus the tools it used. */
final class OutyResult
{
    /** @param string[] $tools */
    public function __construct(
        public readonly string $answer,
        public readonly array $tools = [],
    ) {}
}
