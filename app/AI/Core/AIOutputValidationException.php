<?php

namespace App\AI\Core;

use RuntimeException;

final class AIOutputValidationException extends RuntimeException
{
    /**
     * @param  list<string>  $unknownKeys
     */
    public function __construct(string $message, public readonly array $unknownKeys = [])
    {
        parent::__construct($message);
    }
}
