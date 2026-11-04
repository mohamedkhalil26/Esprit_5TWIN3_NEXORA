<?php

namespace App\Services\Ai\Events;

use App\Models\Evenement;
use App\Services\OpenRouterService;
use Illuminate\Validation\Rule;

final class EventBlueprintService
{
    public function __construct(private readonly OpenRouterService $openRouter) {}

    public function generate(array $input): array
    {
        return $this->openRouter->generate(
            'Prépare un blueprint d’événement professionnel uniquement à partir des informations fournies. Les lieux, dates, prix et capacités inconnus doivent rester explicitement proposés ou null, jamais présentés comme des faits certains. Réponds uniquement en JSON.',
            ['demande' => $input],
            [
                'title' => 'required|string|min:10|max:180',
                'description' => 'required|string|min:30|max:2500',
                'type' => ['required', Rule::in(array_keys(Evenement::TYPES))],
                'objectives' => 'required|array|min:1|max:6',
                'objectives.*' => 'required|string|min:3|max:240',
                'target_audience' => 'required|string|min:3|max:300',
                'suggested_venue' => 'nullable|string|max:180',
                'suggested_city' => 'nullable|string|max:100',
                'suggested_start' => 'nullable|string|max:60',
                'suggested_end' => 'nullable|string|max:60',
                'recommended_capacity' => 'nullable|integer|min:1|max:500',
                'estimated_budget' => 'nullable|numeric|min:0|max:1000000',
                'programme' => 'required|array|min:1|max:8',
                'programme.*' => 'required|string|min:3|max:240',
                'activities' => 'required|array|max:8',
                'activities.*' => 'required|string|min:3|max:240',
                'equipment' => 'required|array|max:12',
                'equipment.*' => 'required|string|min:2|max:180',
                'organisation' => 'required|array|max:8',
                'organisation.*' => 'required|string|min:3|max:240',
            ],
        );
    }
}
