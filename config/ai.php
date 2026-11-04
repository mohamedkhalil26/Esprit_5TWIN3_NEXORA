<?php

return [
    'key' => env('OPENROUTER_API_KEY'),
    'model' => env('OPENROUTER_MODEL') ?: env('OPENROUTER_PREFERRED_FREE_MODEL') ?: 'google/gemma-4-31b-it:free',
    'model_candidate' => env('OPENROUTER_PREFERRED_FREE_MODEL', 'google/gemma-4-31b-it:free'),
    'fallback_model' => env('OPENROUTER_FALLBACK_MODEL') ?: 'google/gemma-4-26b-a4b-it:free',
    'fallback_model_candidate' => env('OPENROUTER_FALLBACK_CANDIDATE', 'google/gemma-4-26b-a4b-it:free'),
    'alternative_model' => env('OPENROUTER_ALTERNATIVE_MODEL', 'nvidia/nemotron-3.5-lightning:free'),
    'connect_timeout' => (int) env('OPENROUTER_CONNECT_TIMEOUT', 4),
    'timeout' => (int) env('OPENROUTER_TIMEOUT', 15),
    'max_tokens' => (int) env('OPENROUTER_MAX_TOKENS', 1800),
    'retry_base_ms' => (int) env('OPENROUTER_RETRY_BASE_MS', 120),
    'retry_jitter_ms' => (int) env('OPENROUTER_RETRY_JITTER_MS', 120),
    'default_rate_limit_cooldown' => (int) env('OPENROUTER_RATE_LIMIT_COOLDOWN', 60),
];
