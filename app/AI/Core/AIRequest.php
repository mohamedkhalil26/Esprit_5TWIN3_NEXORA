<?php

namespace App\AI\Core;

final readonly class AIRequest
{
    public function __construct(
        public string $feature,
        public string $task,
        public array $context,
        public array $rules,
        public string $locale = 'fr',
    ) {}
}
