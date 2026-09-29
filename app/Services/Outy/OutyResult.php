<?php

namespace App\Services\Outy;

/** Final answer from OutyAgent, the tools it used, and any confirm cards to show. */
final class OutyResult
{
    /**
     * @param string[] $tools
     * @param array<int, array{id: string, title: string, details: array, expires_in_minutes: int}> $cards
     */
    public function __construct(
        public readonly string $answer,
        public readonly array $tools = [],
        public readonly array $cards = [],
    ) {}
}
