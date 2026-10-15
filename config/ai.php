<?php

/*
| Any provider with an OpenAI-compatible API works: Gemini (the default below), OpenAI,
| OpenRouter, Groq, or a local Ollama (AI_BASE_URL=http://localhost:11434/v1).
|
| driver: auto  = use the provider when AI_API_KEY is set, otherwise run without AI
|         live  = always use the provider (errors if the key is missing)
|         off   = never call a model; every feature uses its rule-based fallback
*/

return [
    'driver' => env('AI_DRIVER', 'auto'),
    'base_url' => env('AI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/openai'),
    'api_key' => env('AI_API_KEY'),
    'chat_model' => env('AI_CHAT_MODEL', 'gemini-flash-lite-latest'),
    'embedding_model' => env('AI_EMBEDDING_MODEL', 'gemini-embedding-001'),
    'timeout' => (int) env('AI_TIMEOUT', 20),
    'cache_days' => (int) env('AI_CACHE_DAYS', 30),
];
