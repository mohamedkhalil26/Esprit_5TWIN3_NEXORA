<?php

namespace App\AI\Core;

use Illuminate\Support\Facades\Validator;
use JsonException;

final class AIOutputValidator
{
    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     *
     * @throws JsonException
     * @throws AIOutputValidationException
     */
    public function validate(string $content, array $rules): array
    {
        $data = json_decode($content, true, 32, JSON_THROW_ON_ERROR);
        if (! is_array($data) || array_is_list($data)) {
            throw new AIOutputValidationException('La sortie IA doit être un objet JSON.');
        }

        $allowedKeys = collect(array_keys($rules))
            ->map(static fn (string $key): string => explode('.', $key, 2)[0])
            ->unique()
            ->all();
        $unknownKeys = array_values(array_diff(array_keys($data), $allowedKeys));
        if ($unknownKeys !== []) {
            throw new AIOutputValidationException('La sortie IA contient des champs inconnus.', $unknownKeys);
        }

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            throw new AIOutputValidationException('La sortie IA ne respecte pas le contrat attendu.');
        }

        return $validator->validated();
    }
}
