<?php

namespace Tests\Feature;

use App\Models\Categorie;
use App\Models\Equipement;
use App\Models\Evenement;
use App\Models\Participation;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiRecommendationInterfacesTest extends TestCase
{
    use RefreshDatabase;

    public function test_energy_matcher_shows_only_matching_available_equipment_and_explains_scores(): void
    {
        $category = Categorie::factory()->create(['nom' => 'Panneaux']);
        $available = Equipement::factory()->create([
            'categorie_id' => $category->id, 'nom' => 'Panneau solaire adapté', 'disponible' => true,
            'puissance_watts' => 250, 'capacite_wh' => 900, 'ville' => 'Tunis', 'prix_jour' => 15,
        ]);
        Equipement::factory()->create(['nom' => 'Panneau indisponible', 'disponible' => false, 'categorie_id' => $category->id]);

        $this->get(route('ai.recommendations.energy', [
            'need' => 'adapté', 'category' => $category->id, 'power' => 200, 'capacity' => 500, 'budget' => 20, 'city' => 'Tunis',
        ]))->assertOk()->assertSee($available->nom)->assertSee('Compatibilité')->assertSee('Critères satisfaits')
            ->assertDontSee('Panneau indisponible')->assertSee(route('front.equipements.show', $available), false);
    }

    public function test_energy_matcher_validates_filters_and_supports_all_locales(): void
    {
        $this->get(route('ai.recommendations.energy', ['power' => -1]))->assertSessionHasErrors('power');
        $titles = ['fr' => 'Smart Energy Matcher', 'en' => 'Smart Energy Matcher', 'ar' => 'مطابقة الطاقة الذكية'];
        foreach ($titles as $locale => $title) {
            $direction = $locale === 'ar' ? 'rtl' : 'ltr';
            $this->withSession(['locale' => $locale])->get(route('ai.recommendations.energy'))
                ->assertOk()->assertSee('lang="'.$locale.'" dir="'.$direction.'"', false)->assertSee($title);
        }
    }

    public function test_booking_optimizer_checks_conflicts_duration_budget_and_calculated_total(): void
    {
        $user = User::factory()->create();
        $start = today()->addDays(4)->toDateString();
        $end = today()->addDays(6)->toDateString();
        $free = Equipement::factory()->create(['nom' => 'Batterie libre', 'user_id' => User::factory()->create()->id, 'disponible' => true, 'prix_jour' => 10]);
        $busy = Equipement::factory()->create(['nom' => 'Batterie occupée', 'user_id' => User::factory()->create()->id, 'disponible' => true, 'prix_jour' => 8]);
        Reservation::factory()->create([
            'equipement_id' => $busy->id, 'user_id' => User::factory()->create()->id,
            'date_debut' => today()->addDays(5)->toDateString(), 'date_fin' => today()->addDays(5)->toDateString(), 'statut' => 'confirmee',
        ]);

        $this->actingAs($user)->get(route('ai.recommendations.booking', ['date_start' => $start, 'date_end' => $end, 'budget' => 35]))
            ->assertOk()->assertSee($free->nom)->assertDontSee($busy->nom)->assertSee('30,00 DT')->assertSee('3 jour(s)')
            ->assertSee(str_replace('&', '&amp;', route('front.reservations.create', ['equipement' => $free->id, 'date_debut' => $start, 'date_fin' => $end])), false);
        $this->assertDatabaseCount('reservations', 1);

        $this->actingAs($user)->get(route('ai.recommendations.booking', ['date_start' => today()->toDateString(), 'date_end' => today()->addDays(31)->toDateString()]))
            ->assertSessionHasErrors('date_end');
        $this->actingAs($user)->get(route('ai.recommendations.booking', ['date_start' => 'not-a-date', 'date_end' => $end]))
            ->assertSessionHasErrors('date_start');
        $this->actingAs($user)->get(route('ai.recommendations.booking', ['date_start' => $start, 'date_end' => $end, 'budget' => 1]))
            ->assertOk()->assertSee(__('nexora.recommendation.empty'));
    }

    public function test_booking_optimizer_is_private_and_excludes_own_equipment(): void
    {
        $user = User::factory()->create();
        Equipement::factory()->create(['user_id' => $user->id, 'disponible' => true]);
        $this->get(route('ai.recommendations.booking'))->assertRedirect(route('login'));
        $this->actingAs($user)->get(route('ai.recommendations.booking', [
            'date_start' => today()->addDay()->toDateString(), 'date_end' => today()->addDay()->toDateString(),
        ]))->assertOk()->assertSee(__('nexora.recommendation.empty'));
    }

    public function test_event_matchmaker_filters_public_events_calculates_places_and_preserves_registration_workflow(): void
    {
        $event = Evenement::factory()->create([
            'titre' => 'Atelier solaire public', 'statut' => 'planifie', 'type' => 'atelier', 'ville' => 'Tunis',
            'date_debut' => now()->addDays(8), 'date_fin' => now()->addDays(8)->addHours(2), 'prix' => 20, 'capacite' => 2,
        ]);
        $participant = User::factory()->create();
        Participation::factory()->create(['evenement_id' => $event->id, 'user_id' => $participant->id, 'statut' => 'inscrit']);
        Evenement::factory()->create(['titre' => 'Atelier annulé', 'statut' => 'annule', 'date_debut' => now()->addDays(8)]);

        $response = $this->actingAs(User::factory()->create())->get(route('ai.recommendations.events', [
            'type' => 'atelier', 'city' => 'Tunis', 'date' => today()->addDays(5)->toDateString(), 'budget' => 25,
        ]));

        $response->assertOk()->assertSee($event->titre)->assertSee('1 place(s) restante(s)')->assertSee('Compatibilité')
            ->assertDontSee('Atelier annulé')->assertSee(route('front.evenements.inscrire', $event), false);
        $this->assertDatabaseCount('participations', 1);
    }

    public function test_event_matchmaker_shows_full_events_without_registration_and_validates_filters(): void
    {
        $event = Evenement::factory()->create([
            'titre' => 'Atelier complet', 'statut' => 'planifie', 'date_debut' => now()->addDays(5),
            'date_fin' => now()->addDays(5)->addHours(2), 'capacite' => 1, 'prix' => 0,
        ]);
        Participation::factory()->create(['evenement_id' => $event->id, 'user_id' => User::factory()->create()->id, 'statut' => 'inscrit']);

        $this->get(route('ai.recommendations.events', ['budget' => 10]))->assertOk()->assertSee($event->titre)
            ->assertSee(__('nexora.events.full'))->assertDontSee(route('front.evenements.inscrire', $event), false);
        $this->get(route('ai.recommendations.events', ['type' => 'invalid']))->assertSessionHasErrors('type');
    }
}
