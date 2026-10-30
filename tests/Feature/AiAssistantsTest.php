<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Equipement;
use App\Models\Evenement;
use App\Models\Reclamation;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAssistantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_ai_validation_respects_normal_article_field_lengths(): void
    {
        $article = Article::factory()->brouillon()->create();
        $this->actingAs(User::factory()->admin()->create())->post(route('ai.apply', ['module' => 'blog', 'id' => $article->id]), ['titre' => 'Court', 'resume' => 'Résumé trop court'])->assertSessionHasErrors(['titre', 'resume']);
        $this->assertSame('brouillon', $article->fresh()->statut);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['ai.key' => 'test-key']);
    }

    private function response(array $data): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['openrouter.ai/*' => Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode($data)]]]])]);
    }

    public function test_equipment_recommendations_use_real_ids_and_reject_invented_ids(): void
    {
        $item = Equipement::factory()->create(['disponible' => true, 'prix_jour' => 10]);
        $this->actingAs(User::factory()->create());
        $this->response(['equipement_ids' => [$item->id]]);
        $payload = ['besoin' => 'Alimenter une maison avec une batterie', 'budget' => 100];
        $this->post(route('ai.generate', 'equipements'), $payload)->assertOk()->assertSee($item->nom);
        $this->response(['equipement_ids' => [999999]]);
        $this->post(route('ai.generate', 'equipements'), $payload)->assertRedirect()->assertSessionHas('error');
    }

    public function test_reservation_assistant_excludes_conflicts_and_over_budget_candidates(): void
    {
        $user = User::factory()->create();
        $free = Equipement::factory()->create(['disponible' => true, 'prix_jour' => 10]);
        $busy = Equipement::factory()->create(['disponible' => true, 'prix_jour' => 10]);
        Equipement::factory()->create(['disponible' => true, 'prix_jour' => 1000]);
        $start = today()->addDays(3)->toDateString();
        $end = today()->addDays(4)->toDateString();
        Reservation::factory()->create(['equipement_id' => $busy->id, 'date_debut' => $start, 'date_fin' => $end, 'statut' => 'confirmee']);
        $this->response(['equipement_ids' => [$free->id]]);
        $this->actingAs($user)->post(route('ai.generate', 'locations'), ['besoin' => 'Une batterie de secours pour deux jours', 'budget' => 50, 'date_debut' => $start, 'date_fin' => $end])->assertOk();
        Http::assertSent(function ($request) use ($free) {
            $context = json_decode($request['messages'][1]['content'], true);

            return array_column($context['catalogue'], 'id') === [$free->id];
        });
        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_admin_assistants_return_editable_suggestions_without_saving(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $complaint = Reclamation::factory()->create();
        $event = Evenement::factory()->create();
        $article = Article::factory()->create(['statut' => 'brouillon']);
        foreach ([
            ['reclamations', $complaint, ['categorie' => 'technique', 'priorite' => 'haute', 'resume' => 'Problème de batterie', 'reponse' => 'Nous allons examiner votre demande.']],
            ['evenements', $event, ['description' => 'Un atelier pratique autour de l’énergie solaire et de ses usages.', 'programme' => ['Accueil', 'Atelier pratique']]],
            ['blog', $article, ['titre' => 'Titre conseillé', 'resume' => 'Résumé proposé', 'mots_cles' => ['solaire'], 'ameliorations' => ['Préciser les unités']]],
        ] as [$module, $record, $result]) {
            $before = $record->getAttributes();
            $this->response($result);
            $this->post(route('ai.generate', $module), ['id' => $record->id, 'besoin' => 'Propose une analyse structurée en français'])->assertOk()->assertSee('Résultat IA');
            $this->assertEquals($before, $record->fresh()->getAttributes());
        }
    }

    public function test_non_admins_cannot_use_admin_assistants(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['reclamations', 'evenements', 'blog'] as $module) {
            $this->get(route('ai.show', $module))->assertForbidden();
            $this->post(route('ai.generate', $module), [])->assertForbidden();
        }
        Http::assertNothingSent();
    }

    public function test_suggestions_only_persist_after_explicit_admin_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $article = Article::factory()->brouillon()->create();
        $event = Evenement::factory()->create();
        $claim = Reclamation::factory()->create(['statut' => 'ouverte']);
        $this->actingAs($admin)->post(route('ai.apply', ['module' => 'blog', 'id' => $article->id]), ['titre' => 'Titre validé par un administrateur', 'resume' => 'Résumé relu et corrigé par l’administrateur.'])->assertRedirect(route('admin.articles.show', $article));
        $this->assertSame('brouillon', $article->fresh()->statut);
        $this->assertSame('Titre validé par un administrateur', $article->fresh()->titre);
        $this->post(route('ai.apply', ['module' => 'evenements', 'id' => $event->id]), ['description' => 'Une description relue de notre atelier sur l’énergie solaire.', 'programme' => 'Accueil et démonstration pratique'])->assertRedirect();
        $this->assertStringContainsString('Accueil et démonstration pratique', $event->fresh()->description);
        $this->post(route('ai.apply', ['module' => 'reclamations', 'id' => $claim->id]), ['categorie' => 'technique', 'priorite' => 'haute', 'reponse' => 'Votre problème sera examiné par notre équipe.'])->assertRedirect();
        $this->assertSame('haute', $claim->fresh()->priorite);
        $this->assertCount(1, $claim->reponses);
        $this->actingAs(User::factory()->create())->post(route('ai.apply', ['module' => 'blog', 'id' => $article->id]), ['titre' => 'Titre interdit', 'resume' => 'Résumé interdit'])->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_rate_limiting_prevents_excess_ai_requests(): void
    {
        $this->actingAs(User::factory()->create());
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('ai.generate', 'equipements'), [])->assertSessionHasErrors('besoin');
        }
        $this->post(route('ai.generate', 'equipements'), [])->assertStatus(429);
        Http::assertNothingSent();
    }

    public function test_missing_key_api_failure_and_invalid_json_are_explicit(): void
    {
        Equipement::factory()->create(['disponible' => true, 'prix_jour' => 10]);
        $this->actingAs(User::factory()->create());
        $payload = ['besoin' => 'Un équipement pour une maison', 'budget' => 100];
        config(['ai.key' => null]);
        $this->post(route('ai.generate', 'equipements'), $payload)->assertSessionHas('error');
        Http::assertNothingSent();
        config(['ai.key' => 'test-key']);
        Http::swap(new Factory);
        Http::fake(['*' => Http::response([], 429)]);
        $this->post(route('ai.generate', 'equipements'), $payload)->assertSessionHas('error');
        Http::swap(new Factory);
        Http::fake(['*' => Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => 'invalid']]]])]);
        $this->post(route('ai.generate', 'equipements'), $payload)->assertSessionHas('error');
    }
}
