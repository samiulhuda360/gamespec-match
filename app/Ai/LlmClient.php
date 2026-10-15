<?php

namespace App\Ai;

/**
 * The only way the app talks to a language model.
 *
 * Every feature that uses it also has a rule-based path, and switches to it when
 * isLive() is false or a call throws AiException. A model being down, slow or
 * returning nonsense never breaks a page.
 */
interface LlmClient
{
    public function isLive(): bool;

    public function chatModel(): string;

    public function embeddingModel(): string;

    /**
     * Ask for a JSON object that follows $schema (a JSON Schema array).
     *
     * @throws AiException
     */
    public function json(string $feature, string $system, string $user, array $schema, int $maxTokens = 800): array;

    /**
     * @throws AiException
     */
    public function text(string $feature, string $system, string $user, int $maxTokens = 400): string;

    /**
     * @param  list<string>  $texts
     * @return list<list<float>> one vector per text, in order
     *
     * @throws AiException
     */
    public function embed(string $feature, array $texts): array;
}
