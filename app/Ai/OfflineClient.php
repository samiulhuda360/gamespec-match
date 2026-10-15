<?php

namespace App\Ai;

/** Used when no provider is configured: every call refuses, so features use their rules. */
class OfflineClient implements LlmClient
{
    public function isLive(): bool
    {
        return false;
    }

    public function chatModel(): string
    {
        return 'none';
    }

    public function embeddingModel(): string
    {
        return 'none';
    }

    public function json(string $feature, string $system, string $user, array $schema, int $maxTokens = 800): array
    {
        throw new AiException('AI is turned off.');
    }

    public function text(string $feature, string $system, string $user, int $maxTokens = 400): string
    {
        throw new AiException('AI is turned off.');
    }

    public function embed(string $feature, array $texts): array
    {
        throw new AiException('AI is turned off.');
    }
}
