<?php

namespace App\Services\AI;

interface AiProviderInterface
{
    public function generateSummary(array $data): string;
    public function answerQuery(string $question, array $context): string;
    public function generateResponse(string $prompt): string;
    public function explainFlag(array $evidence, string $flagType): string;
    public function isAvailable(): bool;
    public function getProviderName(): string;
}
