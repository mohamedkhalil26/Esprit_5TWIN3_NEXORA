<?php

namespace App\Services\Ai\Scoring;

use App\Models\Equipement;
use App\Models\Evenement;

final class RecommendationScorer
{
    /**
     * @param  array<string, mixed>  $preferences
     * @return array{score: float, confidence: float, components: array<string, float>, missing: list<string>}
     */
    public function scoreEquipment(Equipement $equipment, array $preferences): array
    {
        $components = [];
        $weights = [];
        $missing = [];

        $this->addRatioComponent($components, $weights, $missing, 'puissance', 40, $preferences['puissance_min'] ?? null, $equipment->puissance_watts);
        $this->addRatioComponent($components, $weights, $missing, 'capacite', 40, $preferences['capacite_min'] ?? null, $equipment->capacite_wh);
        $this->addMatchComponent($components, $weights, $missing, 'ville', 10, $preferences['ville'] ?? null, $equipment->ville);
        $this->addMatchComponent($components, $weights, $missing, 'category', 10, $preferences['category_id'] ?? null, $equipment->categorie_id);
        $this->addMatchComponent($components, $weights, $missing, 'etat', 10, $preferences['etat'] ?? null, $equipment->etat);

        return $this->result($components, $weights, $missing, $this->availableCriteria($preferences, ['puissance_min', 'capacite_min', 'ville', 'category_id', 'etat']), 5);
    }

    /**
     * Budget and availability are blocking filters and are deliberately absent here.
     *
     * @param  array<string, mixed>  $preferences
     * @return array{score: float, confidence: float, components: array<string, float>, missing: list<string>}
     */
    public function scoreBooking(Equipement $equipment, array $preferences): array
    {
        $components = [];
        $weights = [];
        $missing = [];

        if (isset($preferences['ville']) && $preferences['ville'] !== '') {
            $components['ville'] = mb_strtolower((string) $equipment->ville) === mb_strtolower((string) $preferences['ville']) ? 1.0 : 0.0;
            $weights['ville'] = 60;
        } else {
            $missing[] = 'ville';
        }
        $components['donnees_equipement'] = $this->equipmentDataQuality($equipment);
        $weights['donnees_equipement'] = 40;

        return $this->result($components, $weights, $missing, count($components), 2);
    }

    /**
     * Publication, date and capacity are blocking filters and are not ranking weights.
     *
     * @param  array<string, mixed>  $preferences
     * @return array{score: float, confidence: float, components: array<string, float>, missing: list<string>}
     */
    public function scoreEvent(Evenement $event, array $preferences): array
    {
        $components = [];
        $weights = [];
        $missing = [];

        $this->addMatchComponent($components, $weights, $missing, 'type', 50, $preferences['type'] ?? null, $event->type);
        $this->addMatchComponent($components, $weights, $missing, 'ville', 30, $preferences['ville'] ?? null, $event->ville);
        if (isset($preferences['date_debut'])) {
            $distance = abs($event->date_debut->startOfDay()->diffInDays($preferences['date_debut'], false));
            $components['date'] = max(0.0, 1.0 - min(30, abs($distance)) / 30);
            $weights['date'] = 20;
        } else {
            $missing[] = 'date_debut';
        }

        return $this->result($components, $weights, $missing, $this->availableCriteria($preferences, ['type', 'ville', 'date_debut']), 3);
    }

    private function addRatioComponent(array &$components, array &$weights, array &$missing, string $name, int $weight, mixed $required, mixed $actual): void
    {
        if ($required === null || $required === '') {
            $missing[] = $name;

            return;
        }
        if (! is_numeric($actual) || (float) $required <= 0) {
            $missing[] = $name;

            return;
        }
        $components[$name] = min(1.0, (float) $actual / (float) $required);
        $weights[$name] = $weight;
    }

    private function addMatchComponent(array &$components, array &$weights, array &$missing, string $name, int $weight, mixed $expected, mixed $actual): void
    {
        if ($expected === null || $expected === '') {
            $missing[] = $name;

            return;
        }
        if ($actual === null || $actual === '') {
            $missing[] = $name;

            return;
        }
        $components[$name] = mb_strtolower((string) $expected) === mb_strtolower((string) $actual) ? 1.0 : 0.0;
        $weights[$name] = $weight;
    }

    private function equipmentDataQuality(Equipement $equipment): float
    {
        $fields = [$equipment->nom, $equipment->description, $equipment->puissance_watts, $equipment->capacite_wh, $equipment->ville, $equipment->etat];
        $available = count(array_filter($fields, static fn ($value) => $value !== null && $value !== ''));

        return $available / count($fields);
    }

    private function availableCriteria(array $preferences, array $keys): int
    {
        return count(array_filter($keys, static fn (string $key): bool => isset($preferences[$key]) && $preferences[$key] !== ''));
    }

    private function result(array $components, array $weights, array $missing, int $requestedCriteria, int $totalCriteria): array
    {
        $weightTotal = array_sum($weights);
        $score = $weightTotal > 0 ? 100 * array_sum(array_map(static fn (string $key): float => $components[$key] * $weights[$key], array_keys($components))) / $weightTotal : 0.0;
        $coverage = $totalCriteria > 0 ? min(1.0, count($components) / $totalCriteria) : 0.0;
        $quality = count($components) > 0 ? array_sum($components) / count($components) : 0.0;

        return [
            'score' => round($score, 2),
            'confidence' => round(100 * (0.7 * $coverage + 0.3 * $quality), 2),
            'components' => array_map(static fn (float $value): float => round($value * 100, 2), $components),
            'missing' => array_values(array_unique($missing)),
        ];
    }
}
