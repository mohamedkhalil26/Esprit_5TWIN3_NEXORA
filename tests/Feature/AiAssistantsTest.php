<?php

namespace Tests\Feature;

use App\AI\Core\AIOutputValidationException;
use App\AI\Core\AIOutputValidator;
use App\AI\Core\AIRequest;
use App\Exceptions\OpenRouterException;
use App\Models\Article;
use App\Models\Equipement;
use App\Models\Evenement;
use App\Models\Reclamation;
use App\Models\Reservation;
use App\Models\User;
use App\Services\OpenRouterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AiAssistantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_failure_diagnostic_contains_no_secret_or_prompt(): void
    {
        Log::spy();
        Equipement::factory()->create(['disponible' => true, 'prix_jour' => 10]);
        Http::fake(['openrouter.ai/*' => Http::response([], 503)]);
        $this->actingAs(User::factory()->create())->post(route('ai.generate', 'equipements'), [
            'besoin' => 'Une demande fictive qui ne doit pas être journalisée', 'budget' => 100,
        ])->assertRedirect()->assertSessionHas('error');
        Log::shouldHaveReceived('warning')->once()->withArgs(function (string $event, array $context): bool {
            $this->assertSame('openrouter.request_failed', $event);
            $this->assertSame('erreur_http', $context['type']);
            $this->assertSame(503, $context['http_status']);
            $this->assertArrayHasKey('preparation_ms', $context);
            $this->assertArrayHasKey('api_ms', $context);
            $this->assertArrayHasKey('parsing_ms', $context);
            $this->assertArrayNotHasKey('prompt', $context);
            $this->assertArrayNotHasKey('api_key', $context);
            $this->assertStringNotContainsString('Une demande fictive', json_encode($context));

            return true;
        });
        Http::assertSentCount(2);
    }

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
        config([
            'ai.key' => 'test-key',
            'ai.model' => 'google/gemma-4-31b-it:free',
            'ai.fallback_model' => 'google/gemma-4-26b-a4b-it:free',
        ]);
    }

    public function test_ai_hub_shows_only_assistants_allowed_for_each_role(): void
    {
        $member = User::factory()->create();
        $this->actingAs($member)->get(route('ai.index'))
            ->assertOk()
            ->assertSee('L’intelligence au service de votre énergie solaire')
            ->assertSee('ai-hub-hero', false)
            ->assertSee('ai-domain-card', false)
            ->assertSee('href="'.route('ai.index').'"', false)
            ->assertSee('href="'.route('ai.recommendations.energy').'"', false)
            ->assertSee('href="'.route('ai.recommendations.booking').'"', false)
            ->assertSee(route('front.equipements.index'))
            ->assertSee(route('front.evenements.index'))
            ->assertSee(route('front.blog.index'))
            ->assertSee(route('tools.compare'))
            ->assertSee(route('ai.recommendations.energy'))
            ->assertSee(route('ai.recommendations.booking'))
            ->assertSee(route('front.reclamations.create'))
            ->assertSee(route('ai.recommendations.events'))
            ->assertSee(route('ai.front.show', 'blog'))
            ->assertSee('SolarMatch')
            ->assertSee('Smart Booking Optimizer')
            ->assertSee('Complaint Doctor')
            ->assertSee('Event Matchmaker')
            ->assertSee('Assistant Blog')
            ->assertDontSee('Front Office — utilisateur')
            ->assertDontSee('Parcours utilisateur')
            ->assertDontSee('Back Office — administrateur')
            ->assertDontSee('Pilotage administrateur')
            ->assertDontSee('Equipment Demand Radar')
            ->assertDontSee('SEO & Editorial Copilot');

        $adminHub = $this->actingAs(User::factory()->admin()->create())->get(route('ai.index'))
            ->assertOk()
            ->assertSee('ai-domain-card', false)
            ->assertDontSee('ai-assistant-panel-bo', false)
            ->assertSee('href="'.route('ai.index').'"', false)
            ->assertSee('Smart Booking Optimizer')
            ->assertSee(route('admin.reclamations.intelligence'))
            ->assertSee(route('ai.admin.blueprints.index'))
            ->assertSee(route('ai.admin.insights.index'))
            ->assertSee(route('ai.admin.feedback.index'))
            ->assertDontSee('Equipment Demand Radar')
            ->assertDontSee('SEO & Editorial Copilot');
    }

    public function test_public_ai_hub_lists_real_front_office_routes_and_the_homepage_links_to_it(): void
    {
        $this->get(route('ai.index'))
            ->assertOk()
            ->assertSee('L’intelligence au service de votre énergie solaire')
            ->assertSee('ai-hub-hero', false)
            ->assertSee('href="'.route('ai.recommendations.energy').'"', false)
            ->assertSee('href="'.route('ai.recommendations.events').'"', false)
            ->assertDontSee('href="'.route('ai.recommendations.booking').'"', false)
            ->assertDontSee('href="'.route('front.reclamations.create').'"', false)
            ->assertDontSee('href="'.route('ai.front.show', 'blog').'"', false)
            ->assertSee('Se connecter pour commencer')
            ->assertDontSee('Equipment Demand Radar')
            ->assertDontSee('SEO & Editorial Copilot');
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('ai.index').'"', false)
            ->assertSee('Centre IA')
            ->assertSee('SolarMatch')
            ->assertSee('Event Matchmaker')
            ->assertSee('Se connecter pour commencer');

        // Exposing the directory does not expose the member-specific assistant endpoints.
        $this->get(route('ai.show', 'equipements'))->assertRedirect(route('login'));
        $this->get(route('ai.front.show', 'blog'))->assertRedirect(route('login'));

        $unverified = User::factory()->unverified()->create();
        $this->actingAs($unverified)->get(route('ai.front.show', 'blog'))->assertRedirect(route('verification.notice'));
    }

    public function test_all_five_assistant_forms_render_their_matching_post_route(): void
    {
        $member = User::factory()->create();
        foreach (['equipements', 'locations'] as $module) {
            $this->actingAs($member)->get(route('ai.show', $module))
                ->assertOk()
                ->assertSee('action="'.route('ai.generate', $module).'"', false)
                ->assertSee('name="besoin"', false)
                ->assertSee('name="budget"', false);
        }

        $admin = User::factory()->admin()->create();
        $records = [
            'reclamations' => Reclamation::factory()->create(),
            'evenements' => Evenement::factory()->create(),
            'blog' => Article::factory()->brouillon()->create(),
        ];
        foreach ($records as $module => $record) {
            $this->actingAs($admin)->get(route('ai.show', $module))
                ->assertOk()
                ->assertSee('action="'.route('ai.generate', $module).'"', false)
                ->assertSee('name="id"', false)
                ->assertSee('name="besoin"', false)
                ->assertSee((string) $record->getKey());
        }
    }

    public function test_assistant_displays_flash_errors_and_offers_retry_after_a_result(): void
    {
        $this->actingAs(User::factory()->create())
            ->withSession(['error' => 'Le service IA est temporairement limité. Réessayez dans environ 2 minutes.'])
            ->get(route('ai.show', 'equipements'))
            ->assertOk()
            ->assertSee('Le service IA est temporairement limité. Réessayez dans environ 2 minutes.')
            ->assertSee('role="alert"', false);

        Equipement::factory()->create(['disponible' => true, 'prix_jour' => 10]);
        $this->response(['equipement_ids' => []]);
        $this->post(route('ai.generate', 'equipements'), [
            'besoin' => 'Un besoin fictif d’équipement solaire adapté.',
            'budget' => 100,
        ])->assertOk()->assertSee('Réessayer la génération');
    }

    private function response(array $data): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['openrouter.ai/*' => Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode($data)]]]])]);
    }

    private function sequence(array $contents, array $finishReasons = []): void
    {
        $sequence = Http::fakeSequence();
        foreach ($contents as $index => $content) {
            $sequence->push([
                'choices' => [[
                    'finish_reason' => $finishReasons[$index] ?? 'stop',
                    'message' => ['content' => $content],
                ]],
            ]);
        }
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

    public function test_equipment_recommendations_expose_server_calculated_score(): void
    {
        $preferred = Equipement::factory()->create(['disponible' => true, 'prix_jour' => 10, 'puissance_watts' => 200, 'ville' => 'Tunis', 'etat' => 'bon']);
        Equipement::factory()->create(['disponible' => true, 'prix_jour' => 10, 'puissance_watts' => 50, 'ville' => 'Sousse', 'etat' => 'usage']);
        $this->actingAs(User::factory()->create());
        $this->response(['equipement_ids' => [$preferred->id]]);

        $this->post(route('ai.generate', 'equipements'), [
            'besoin' => 'Un équipement puissant à utiliser à Tunis.',
            'budget' => 100,
            'ville' => 'Tunis',
            'puissance_min' => 100,
        ])->assertOk()->assertSee('Compatibilité calculée : 100,00/100');
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
            ['reclamations', $complaint, ['categorie' => 'technique', 'priorite' => 'haute', 'resume' => 'Problème de batterie', 'reponse' => 'Nous allons examiner votre demande.', 'informations_manquantes' => ['Numéro de série'], 'explication' => 'Le texte décrit une panne matérielle.']],
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

    public function test_openrouter_429_returns_safe_retry_after_message_without_retrying(): void
    {
        Equipement::factory()->create(['disponible' => true, 'prix_jour' => 10]);
        $this->actingAs(User::factory()->create());
        Http::fakeSequence()->push([], 429, ['Retry-After' => '120']);

        $this->post(route('ai.generate', 'equipements'), [
            'besoin' => 'Un besoin fictif de matériel solaire.',
            'budget' => 100,
        ])->assertRedirect()->assertSessionHas('error', 'OpenRouter a limité cette requête (429), sans préciser la portée de la limite. Aucun autre modèle ne sera essayé; changer de modèle peut ne pas aider. Réessayez après le renouvellement ou le délai indiqué.');

        Http::assertSentCount(1);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_openrouter_retry_after_blocks_a_second_provider_request(): void
    {
        Http::fakeSequence()->push([], 429, ['Retry-After' => '120']);

        try {
            app(OpenRouterService::class)->generate('Demande synthétique.', ['fictif' => true], ['ok' => 'required|boolean']);
            $this->fail('Le fournisseur a limité cette requête.');
        } catch (OpenRouterException $exception) {
            $this->assertSame(429, $exception->httpStatus);
            $this->assertSame(120, $exception->retryAfterSeconds);
        }

        try {
            app(OpenRouterService::class)->generate('Nouvelle demande synthétique.', ['fictif' => true], ['ok' => 'required|boolean']);
            $this->fail('La seconde requête doit être bloquée par Retry-After.');
        } catch (OpenRouterException $exception) {
            $this->assertSame(429, $exception->httpStatus);
            $this->assertGreaterThan(0, $exception->retryAfterSeconds);
        }

        Http::assertSentCount(1);
    }

    public function test_openrouter_shared_free_daily_quota_does_not_try_another_free_model(): void
    {
        Http::fakeSequence('openrouter.ai/*')->push([
            'error' => ['code' => 'rate_limit_exceeded', 'message' => 'Rate limit exceeded: free-models-per-day'],
        ], 429);

        try {
            app(OpenRouterService::class)->generate('Demande synthétique.', ['fictif' => true], ['ok' => 'required|boolean']);
            $this->fail('Le quota gratuit commun doit interrompre la demande.');
        } catch (OpenRouterException $exception) {
            $this->assertSame(429, $exception->httpStatus);
            $this->assertStringContainsString('quota gratuit partagé du compte', $exception->getMessage());
            $this->assertStringContainsString('Aucun autre modèle ne sera essayé', $exception->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_openrouter_provider_scoped_429_stops_without_fallback(): void
    {
        Http::fakeSequence('openrouter.ai/*')
            ->push(['error' => ['message' => 'Provider rate limit exceeded for this model']], 429, ['Retry-After' => '3'])
            ->push(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => '{"ok":true}']]]]);

        try {
            app(OpenRouterService::class)->generate('Demande synthétique.', ['fictif' => true], ['ok' => 'required|boolean']);
            $this->fail('Une limite 429 propre au fournisseur doit arrêter immédiatement la demande.');
        } catch (OpenRouterException $exception) {
            $this->assertSame(429, $exception->httpStatus);
            $this->assertStringContainsString('Aucun modèle de secours ne sera essayé', $exception->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_openrouter_timeout_uses_only_one_temporary_fallback_attempt(): void
    {
        $attempts = 0;
        Http::globalRequestMiddleware(static function ($request) use (&$attempts) {
            if (str_starts_with((string) $request->getUri(), 'https://openrouter.ai/api/v1/chat/completions')) {
                $attempts++;
            }

            return $request;
        });
        Http::fake(static function () {
            throw new ConnectionException('cURL error 28: Operation timed out after 60000 milliseconds');
        });

        try {
            app(OpenRouterService::class)->generate('Demande entièrement synthétique.', ['fictif' => true], ['ok' => 'required|boolean']);
            $this->fail('Le délai d’attente doit être signalé.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('La requête vers le fournisseur IA a expiré. Réessayez dans quelques instants.', $exception->getMessage());
        }

        $this->assertSame(2, $attempts);
    }

    public function test_openrouter_http_statuses_have_safe_messages_and_only_configured_fallbacks_for_temporary_errors(): void
    {
        foreach ([
            400 => 'OpenRouter a refusé cette demande.',
            401 => 'Le service IA n’a pas accepté sa configuration d’accès.',
            402 => 'Le quota de crédit du service IA est épuisé.',
            403 => 'Le service IA n’autorise pas ce modèle ou cette demande.',
            404 => 'Le modèle IA configuré est indisponible.',
            500 => 'Le service IA rencontre une panne temporaire.',
            429 => 'OpenRouter a limité cette requête (429), sans préciser la portée de la limite.',
        ] as $status => $expectedMessage) {
            Http::swap(new Factory);
            Http::preventStrayRequests();
            Http::fake(['openrouter.ai/*' => Http::response(['error' => ['message' => 'private-provider-detail']], $status)]);
            config(['ai.fallback_model' => null]);

            try {
                app(OpenRouterService::class)->generate('Demande synthétique non sensible.', ['fictif' => true], ['ok' => 'required|boolean']);
                $this->fail("HTTP $status doit échouer.");
            } catch (OpenRouterException $exception) {
                $this->assertSame($status, $exception->httpStatus);
                $this->assertStringContainsString($expectedMessage, $exception->getMessage());
                $this->assertStringNotContainsString('private-provider-detail', $exception->getMessage());
                $this->assertSame($status === 429 ? 60 : null, $exception->retryAfterSeconds);
            }

            Http::assertSentCount(1);
        }
    }

    public function test_openrouter_rejects_truncated_json_without_calling_fallback(): void
    {
        Http::fake(['openrouter.ai/*' => Http::response([
            'choices' => [[
                'finish_reason' => 'length',
                'message' => ['content' => '{"equipement_ids":[17'],
            ]],
        ])]);

        try {
            app(OpenRouterService::class)->generate('Choisir un équipement fictif.', ['synthetique' => true], ['equipement_ids' => 'required|array']);
            $this->fail('Une génération tronquée doit être rejetée.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('tronquée', $exception->getMessage());
        }
        Http::assertSentCount(1);
    }

    public function test_openrouter_rejects_invalid_json_without_calling_fallback(): void
    {
        $this->sequence(['{invalid', '{still invalid']);

        try {
            app(OpenRouterService::class)->generate('Tâche fictive.', ['synthetique' => true], ['titre' => 'required|string']);
            $this->fail('Une réponse JSON invalide ne doit pas être retournée.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Le service IA n’a pas fourni une réponse structurée valide. Réessayez plus tard.', $exception->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_openrouter_rejects_a_schema_error_without_calling_fallback(): void
    {
        $this->sequence(['{"titre":17}', '{"titre":false}']);

        try {
            app(OpenRouterService::class)->generate('Tâche fictive.', ['synthetique' => true], ['titre' => 'required|string']);
            $this->fail('Une réponse hors schéma ne doit pas être retournée.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Le service IA n’a pas fourni une réponse structurée valide. Réessayez plus tard.', $exception->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_openrouter_does_not_retry_non_temporary_http_errors(): void
    {
        Http::fakeSequence()->push([], 401);

        try {
            app(OpenRouterService::class)->generate('Tâche fictive.', ['synthetique' => true], ['titre' => 'required|string']);
            $this->fail('Une erreur d’authentification ne doit pas produire de résultat.');
        } catch (\RuntimeException) {
            $this->assertTrue(true);
        }

        Http::assertSentCount(1);
    }

    public function test_openrouter_sends_json_mode_and_falls_back_to_the_configured_compatible_model(): void
    {
        Http::fakeSequence('openrouter.ai/*')
            ->push([], 503)
            ->push(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => '{"ok":true}']]]]);

        $result = app(OpenRouterService::class)->generate('Répondre en JSON.', ['synthetique' => true], ['ok' => 'required|boolean']);

        $this->assertSame(['ok' => true], $result);
        $requests = Http::recorded()->map(fn ($pair) => $pair[0])->values();
        $this->assertSame('google/gemma-4-31b-it:free', $requests[0]['model']);
        $this->assertSame(config('ai.fallback_model'), $requests[1]['model']);
        $this->assertSame(['type' => 'json_object'], $requests[0]['response_format']);
        $this->assertSame(1800, $requests[0]['max_tokens']);
        $this->assertSame(['type' => 'json_object'], $requests[1]['response_format']);
        $this->assertArrayNotHasKey('temperature', $requests[0]);
        Http::assertSentCount(2);
    }

    public function test_openrouter_stops_after_a_single_failed_fallback(): void
    {
        Http::fakeSequence('openrouter.ai/*')
            ->push([], 503)
            ->push([], 503);

        try {
            app(OpenRouterService::class)->generate('Demande synthétique.', ['fictif' => true], ['ok' => 'required|boolean']);
            $this->fail('Deux échecs doivent terminer le parcours.');
        } catch (OpenRouterException $exception) {
            $this->assertSame(503, $exception->httpStatus);
        }

        Http::assertSentCount(2);
    }

    public function test_openrouter_uses_one_fallback_for_503_even_when_retry_after_is_present(): void
    {
        Http::fakeSequence('openrouter.ai/*')
            ->push([], 503, ['Retry-After' => '30'])
            ->push(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => '{"ok":true}']]]]);

        $this->assertSame(['ok' => true], app(OpenRouterService::class)->generate(
            'Tâche fictive.', ['synthetique' => true], ['ok' => 'required|boolean'],
        ));
        Http::assertSentCount(2);
    }

    public function test_openrouter_extracts_json_surrounded_by_prose_or_markdown_before_validation(): void
    {
        Http::fake(['openrouter.ai/*' => Http::response(['choices' => [[
            'finish_reason' => 'stop',
            'message' => ['content' => "Voici le résultat :\n```json\n{\"ok\":true}\n```\nFin."],
        ]]])]);

        $this->assertSame(['ok' => true], app(OpenRouterService::class)->generate(
            'Retourner un booléen.',
            ['synthetique' => true],
            ['ok' => 'required|boolean'],
        ));
        Http::assertSentCount(1);
    }

    public function test_ai_request_contract_keeps_the_legacy_provider_compatible(): void
    {
        Http::fake(['openrouter.ai/*' => Http::response(['choices' => [[
            'finish_reason' => 'stop',
            'message' => ['content' => '{"ok":true}'],
        ]]])]);

        $result = app(OpenRouterService::class)->generateRequest(new AIRequest(
            'lot-0-test',
            'Retourne un objet JSON minimal.',
            ['synthetique' => true],
            ['ok' => 'required|boolean'],
        ));

        $this->assertSame(['ok' => true], $result);
        Http::assertSentCount(1);
    }

    public function test_json_contract_rejects_unknown_top_level_keys(): void
    {
        $this->expectException(AIOutputValidationException::class);

        app(AIOutputValidator::class)->validate('{"ok":true,"unexpected":"private"}', ['ok' => 'required|boolean']);
    }

    public function test_live_campaign_http_middleware_counts_a_faked_request_without_network_access(): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['openrouter.ai/*' => Http::response([
            'model' => 'openrouter/free',
            'choices' => [['finish_reason' => 'stop', 'message' => ['content' => '{"ok":true}']]],
        ])]);
        $attempts = 0;
        Http::globalRequestMiddleware(static function ($request) use (&$attempts) {
            if (str_starts_with((string) $request->getUri(), 'https://openrouter.ai/api/v1/chat/completions')) {
                $attempts++;
            }

            return $request;
        });

        app(OpenRouterService::class)->generate('Tâche fictive.', ['synthetique' => true], ['ok' => 'required|boolean']);

        $this->assertSame(1, $attempts);
        Http::assertSentCount(1);
    }
}
