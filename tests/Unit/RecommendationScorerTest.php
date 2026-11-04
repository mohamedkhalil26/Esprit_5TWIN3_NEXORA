<?php

namespace Tests\Unit;

use App\Models\Equipement;
use App\Models\Evenement;
use App\Services\Ai\Scoring\RecommendationScorer;
use Tests\TestCase;

class RecommendationScorerTest extends TestCase
{
    public function test_energy_score_renormalizes_only_available_preferences(): void
    {
        $equipment = new Equipement(['nom' => 'Batterie test', 'description' => 'Donnée synthétique', 'puissance_watts' => 100, 'capacite_wh' => 500, 'ville' => 'Tunis', 'etat' => 'bon']);
        $result = app(RecommendationScorer::class)->scoreEquipment($equipment, ['puissance_min' => 50]);

        $this->assertSame(100.0, $result['score']);
        $this->assertSame(['capacite', 'ville', 'category', 'etat'], $result['missing']);
        $this->assertSame(44.0, $result['confidence']);
    }

    public function test_booking_score_does_not_use_blocking_budget_or_availability(): void
    {
        $equipment = new Equipement(['nom' => 'Batterie test', 'description' => 'Donnée synthétique', 'prix_jour' => 10, 'disponible' => false]);
        $scorer = app(RecommendationScorer::class);

        $withoutBlockingData = $scorer->scoreBooking($equipment, []);
        $withBlockingData = $scorer->scoreBooking($equipment, ['budget' => 1, 'date_debut' => '2030-01-01']);

        $this->assertSame($withoutBlockingData['score'], $withBlockingData['score']);
        $this->assertArrayNotHasKey('budget', $withBlockingData['components']);
    }

    public function test_event_score_uses_only_preferences_and_handles_missing_date(): void
    {
        $event = new Evenement(['type' => 'atelier', 'ville' => 'Tunis', 'date_debut' => '2030-01-10']);
        $event->date_debut = now()->addDays(10);
        $result = app(RecommendationScorer::class)->scoreEvent($event, ['type' => 'atelier']);

        $this->assertSame(100.0, $result['score']);
        $this->assertContains('ville', $result['missing']);
        $this->assertContains('date_debut', $result['missing']);
    }

    public function test_empty_preferences_do_not_divide_by_zero(): void
    {
        $equipment = new Equipement;
        $result = app(RecommendationScorer::class)->scoreEquipment($equipment, []);

        $this->assertSame(0.0, $result['score']);
        $this->assertSame(0.0, $result['confidence']);
    }
}
