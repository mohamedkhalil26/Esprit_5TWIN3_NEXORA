<?php

namespace App\Services\Ai;

class HubCatalog
{
    /**
     * Return the five existing public-facing AI journeys with links generated
     * from their named Laravel routes. Account-gated tools offer login to guests.
     *
     * @return list<array<string, mixed>>
     */
    public function modules(): array
    {
        $definitions = [
            'equipements' => ['sun', 'ai.recommendations.energy', [], false],
            'locations' => ['calendar-check', 'ai.recommendations.booking', [], true],
            'reclamations' => ['chat-square-text', 'front.reclamations.create', [], true],
            'evenements' => ['calendar-event', 'ai.recommendations.events', [], false],
            'blog' => ['journal-text', 'ai.front.show', ['module' => 'blog'], true],
        ];
        $modules = [];

        foreach ($definitions as $module => [$icon, $frontRoute, $frontParameters, $requiresAuth]) {
            $modules[] = [
                'key' => $module,
                'icon' => $icon,
                'name' => __('ai_hub.card_labels.'.$module),
                'requires_auth' => $requiresAuth,
                'front' => [
                    'title' => __('ai_hub.modules.'.$module.'.front_title'),
                    'description' => __('ai_hub.modules.'.$module.'.front_description'),
                    'url' => route($frontRoute, $frontParameters),
                ],
            ];
        }

        return $modules;
    }
}
