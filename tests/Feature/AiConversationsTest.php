<?php

namespace Tests\Feature;

use App\Models\AiConversation;
use App\Models\Article;
use App\Models\Equipement;
use App\Models\Evenement;
use App\Models\Reclamation;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiConversationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['ai.key' => 'test-key']);
    }

    public function test_hub_shows_five_front_cards_and_keeps_admin_experiences_in_the_back_office(): void
    {
        $member = User::factory()->create();
        $frontResponse = $this->actingAs($member)->get(route('ai.index'))->assertOk()
            ->assertSee(route('ai.recommendations.energy'))
            ->assertSee(route('ai.recommendations.booking'))
            ->assertSee(route('front.reclamations.create'))
            ->assertSee(route('ai.recommendations.events'))
            ->assertSee(route('ai.front.show', 'blog'))
            ->assertDontSee('Equipment Demand Radar')
            ->assertDontSee('SEO & Editorial Copilot');
        $this->assertSame(5, substr_count($frontResponse->getContent(), 'class="ai-domain-card ai-card-'));

        $admin = User::factory()->admin()->create();
        $response = $this->actingAs($admin)->get(route('ai.index'))->assertOk()
            ->assertSee(route('ai.recommendations.energy'))
            ->assertSee(route('ai.admin.show', 'equipements'))
            ->assertSee(route('ai.recommendations.booking'))
            ->assertSee(route('ai.admin.show', 'locations'))
            ->assertSee(route('front.reclamations.create'))
            ->assertSee(route('admin.reclamations.intelligence'))
            ->assertSee(route('ai.recommendations.events'))
            ->assertSee(route('ai.admin.blueprints.index'))
            ->assertSee(route('ai.admin.insights.index'))
            ->assertSee(route('ai.admin.feedback.index'))
            ->assertSee(route('ai.front.show', 'blog'))
            ->assertSee(route('ai.admin.show', 'blog'));
        $this->assertSame(5, substr_count($response->getContent(), 'class="ai-domain-card ai-card-'));
    }

    public function test_front_office_has_five_specialized_routes_and_admin_routes_are_protected(): void
    {
        $member = User::factory()->create();
        foreach (['equipements', 'locations', 'reclamations', 'evenements', 'blog'] as $module) {
            $this->actingAs($member)->get(route('ai.front.show', $module))->assertOk();
            $this->get(route('ai.admin.show', $module))->assertForbidden();
        }

        $admin = User::factory()->admin()->create();
        foreach (['equipements', 'locations', 'reclamations', 'evenements', 'blog'] as $module) {
            $this->actingAs($admin)->get(route('ai.admin.show', $module))->assertOk();
        }
    }

    public function test_equipment_chat_uses_real_available_candidates_and_encrypts_saved_messages(): void
    {
        $member = User::factory()->create();
        $candidate = Equipement::factory()->create(['disponible' => true]);
        Equipement::factory()->create(['disponible' => false]);
        Http::fake(['openrouter.ai/*' => Http::response(['model' => 'openrouter/free', 'choices' => [[
            'finish_reason' => 'stop',
            'message' => ['content' => json_encode(['answer' => 'Voici une option disponible.', 'equipment_ids' => [$candidate->id]])],
        ]]])]);

        $response = $this->actingAs($member)->post(route('ai.front.send', 'equipements'), [
            'question' => 'Je cherche un panneau pour une petite maison.',
            'budget' => 100,
        ])->assertRedirect();
        $conversation = $member->aiConversations()->sole();
        $this->assertSame('front', $conversation->audience);
        $this->assertDatabaseCount('ai_messages', 2);
        $this->assertStringNotContainsString('petite maison', DB::table('ai_messages')->where('role', 'user')->value('content'));
        Http::assertSent(fn ($request) => $request['model'] === 'google/gemma-4-31b-it:free'
            && str_contains($request['messages'][1]['content'], '"id":'.$candidate->id));
        $this->get($response->headers->get('Location'))->assertOk()->assertSee($candidate->nom);
        $this->assertDatabaseHas('ai_feature_runs', ['user_id' => $member->id, 'feature' => 'equipements', 'status' => 'completed']);
    }

    public function test_rate_limited_generation_keeps_the_question_but_does_not_store_an_ai_answer(): void
    {
        $member = User::factory()->create();
        Http::fakeSequence('openrouter.ai/*')->push([], 429, ['Retry-After' => '120']);

        $response = $this->actingAs($member)->post(route('ai.front.send', 'equipements'), [
            'question' => 'Conseil fictif pour du matériel solaire.',
        ])->assertRedirect()->assertSessionHas('ai_response_error_message', __('ai_conversation.errors.rate_limited'));
        $this->get($response->headers->get('Location'))->assertOk()
            ->assertSee('L’assistant n’a pas pu générer de réponse.')
            ->assertSee('Réessayer')
            ->assertSee(__('ai_conversation.errors.rate_limited'));

        $conversation = $member->aiConversations()->sole();
        $this->assertDatabaseCount('ai_messages', 1);
        $this->assertDatabaseHas('ai_messages', ['conversation_id' => $conversation->id, 'role' => 'user']);
        $this->assertDatabaseMissing('ai_messages', ['conversation_id' => $conversation->id, 'role' => 'assistant']);
        Http::assertSentCount(1);
        $this->assertAllRequestsUseTheConfiguredGemmaModel();
    }

    public function test_admin_failure_is_shown_inline_and_retry_reuses_the_saved_question(): void
    {
        $admin = User::factory()->admin()->create();
        config(['ai.model' => '']);

        $response = $this->actingAs($admin)->post(route('ai.admin.send', 'locations'), [
            'question' => 'Quels indicateurs de réservation dois-je examiner ?',
        ])->assertRedirect()->assertSessionHas('ai_response_error', true)
            ->assertSessionHas('ai_response_error_message', __('ai_conversation.errors.model_not_ready'));

        $conversation = $admin->aiConversations()->sole();
        $location = $response->headers->get('Location');
        $this->get($location)->assertOk()
            ->assertSee('Quels indicateurs de réservation dois-je examiner ?')
            ->assertSee('L’assistant n’a pas pu générer de réponse.')
            ->assertSee(__('ai_conversation.errors.model_not_ready'))
            ->assertSee('Réessayer')
            ->assertSee('À l’instant')
            ->assertSee('id="ai-loading" class="small text-primary mt-2 mb-0 d-none"', false)
            ->assertDontSee('feedback-type')
            ->assertDontSee('alert-danger', false);

        $this->assertDatabaseCount('ai_messages', 1);
        $this->assertSame('Quels indicateurs de réservation dois-je examiner ?', $conversation->messages()->sole()->content);
        Http::assertNothingSent();

        config(['ai.model' => 'test-validated-free-model']);
        Http::fake(['openrouter.ai/*' => Http::response(['choices' => [[
            'finish_reason' => 'stop',
            'message' => ['content' => json_encode([
                'summary' => 'Les réservations montrent plusieurs tendances à examiner.',
                'insights' => ['Le volume de réservations est suivi dans les données.'],
                'recommendations' => ['Comparer les réservations par période.'],
            ])],
        ]]])]);

        $this->post(route('ai.admin.send', 'locations'), [
            'question' => 'Quels indicateurs de réservation dois-je examiner ?',
            'conversation_id' => $conversation->id,
            'retry' => '1',
        ])->assertRedirect();

        $conversation->refresh();
        $this->assertDatabaseCount('ai_messages', 2);
        $this->assertSame(1, $conversation->messages()->where('role', 'user')->count());
        $this->assertSame(1, $conversation->messages()->where('role', 'assistant')->count());
        $this->get(route('ai.admin.show', ['module' => 'locations', 'conversation_id' => $conversation->id]))
            ->assertOk()->assertSee('Les réservations montrent plusieurs tendances à examiner.')
            ->assertSee('feedback-type');
        Http::assertSentCount(1);
    }

    public function test_admin_rate_limit_is_clear_stops_fallback_and_keeps_the_question_in_history(): void
    {
        $admin = User::factory()->admin()->create();
        Http::fakeSequence('openrouter.ai/*')
            ->push(['error' => ['code' => 'rate_limit_exceeded', 'message' => 'Rate limit exceeded: free-models-per-day']], 429, ['Retry-After' => '120'])
            ->push(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => '{"summary":"must not be used"}']]]]);

        $response = $this->actingAs($admin)->post(route('ai.admin.send', 'locations'), [
            'question' => 'Résume les réservations de manière synthétique.',
        ])->assertRedirect()->assertSessionHas('ai_response_error', true)
            ->assertSessionHas('ai_response_error_message', __('ai_conversation.errors.rate_limited'));

        $conversation = $admin->aiConversations()->sole();
        $this->get($response->headers->get('Location'))->assertOk()
            ->assertSee('Résume les réservations de manière synthétique.')
            ->assertSee('L’assistant n’a pas pu générer de réponse.')
            ->assertSee(__('ai_conversation.errors.rate_limited'))
            ->assertSee('Réessayer')
            ->assertDontSee('feedback-type');

        $this->assertDatabaseCount('ai_messages', 1);
        $this->assertDatabaseHas('ai_messages', ['conversation_id' => $conversation->id, 'role' => 'user']);
        $this->assertDatabaseMissing('ai_messages', ['conversation_id' => $conversation->id, 'role' => 'assistant']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['model'] === 'google/gemma-4-31b-it:free');
    }

    public function test_admin_assistant_titles_follow_french_english_and_arabic_locales(): void
    {
        $admin = User::factory()->admin()->create();
        $titles = [
            'fr' => [
                'equipements' => 'Tendances de demande', 'locations' => 'Analyse des réservations', 'reclamations' => 'Intelligence des réclamations', 'evenements' => 'Blueprints événementiels', 'blog' => 'Assistant éditorial et SEO',
            ],
            'en' => [
                'equipements' => 'Equipment Demand Radar', 'locations' => 'Booking Insights', 'reclamations' => 'Complaint Intelligence Center', 'evenements' => 'Event Blueprint Generator', 'blog' => 'SEO & Editorial Copilot',
            ],
            'ar' => [
                'equipements' => 'رادار طلب المعدات', 'locations' => 'رؤى الحجوزات', 'reclamations' => 'مركز ذكاء الشكاوى', 'evenements' => 'منشئ مخطط الفعالية', 'blog' => 'مساعد SEO والتحرير',
            ],
        ];

        foreach ($titles as $locale => $moduleTitles) {
            foreach ($moduleTitles as $module => $title) {
                $this->withSession(['locale' => $locale])->actingAs($admin)
                    ->get(route('ai.admin.show', $module))->assertOk()->assertSee($title);
            }
        }

        $this->withSession(['locale' => 'ar'])->actingAs($admin)
            ->get(route('ai.admin.show', 'locations'))->assertSee('lang="ar" dir="rtl"', false);
    }

    public function test_booking_copilot_filters_date_conflicts_and_over_budget_equipment(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        $start = now()->addDays(15)->toDateString();
        $end = now()->addDays(16)->toDateString();
        $conflict = Equipement::factory()->create(['user_id' => $other->id, 'disponible' => true, 'prix_jour' => 30]);
        $candidate = Equipement::factory()->create(['user_id' => $other->id, 'disponible' => true, 'prix_jour' => 40]);
        $overBudget = Equipement::factory()->create(['user_id' => $other->id, 'disponible' => true, 'prix_jour' => 60]);
        Reservation::factory()->create([
            'equipement_id' => $conflict->id,
            'date_debut' => $start,
            'date_fin' => $end,
            'nb_jours' => 2,
            'statut' => 'confirmee',
        ]);
        Http::fake(['openrouter.ai/*' => Http::response(['choices' => [[
            'finish_reason' => 'stop',
            'message' => ['content' => json_encode(['answer' => 'Cette option est libre et dans votre budget.', 'equipment_ids' => [$candidate->id]])],
        ]]])]);

        $this->actingAs($member)->post(route('ai.front.send', 'locations'), [
            'question' => 'Je cherche un équipement pour ces dates.',
            'budget' => 100,
            'date_debut' => $start,
            'date_fin' => $end,
        ])->assertRedirect();

        Http::assertSent(function ($request) use ($candidate, $conflict, $overBudget) {
            $context = $request['messages'][1]['content'];

            return $request['model'] === 'google/gemma-4-31b-it:free'
                && str_contains($context, '"id":'.$candidate->id)
                && ! str_contains($context, '"id":'.$conflict->id)
                && ! str_contains($context, '"id":'.$overBudget->id);
        });
    }

    public function test_claim_companion_sends_only_the_signed_in_users_own_claims(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        Reclamation::factory()->create(['user_id' => $member->id, 'sujet' => 'SYNTHETIC_OWN_CLAIM']);
        Reclamation::factory()->create(['user_id' => $other->id, 'sujet' => 'SYNTHETIC_OTHER_CLAIM']);
        Http::fake(['openrouter.ai/*' => Http::response(['choices' => [[
            'finish_reason' => 'stop',
            'message' => ['content' => json_encode(['answer' => 'Voici un brouillon à relire.', 'draft' => 'Mon chargeur ne fonctionne plus.', 'category' => 'equipement', 'priority' => 'moyenne'])],
        ]]])]);

        $this->actingAs($member)->post(route('ai.front.send', 'reclamations'), ['question' => 'Aide-moi à clarifier mon problème de matériel.'])->assertRedirect();
        Http::assertSent(function ($request) {
            $context = json_decode($request['messages'][1]['content'], true);
            $json = json_encode($context, JSON_UNESCAPED_UNICODE);

            return $request['model'] === 'google/gemma-4-31b-it:free'
                && str_contains($json, 'SYNTHETIC_OWN_CLAIM')
                && ! str_contains($json, 'SYNTHETIC_OTHER_CLAIM');
        });
    }

    public function test_event_and_blog_assistants_return_only_real_public_records(): void
    {
        $member = User::factory()->create();
        $event = Evenement::factory()->create(['date_debut' => now()->addDays(10), 'date_fin' => now()->addDays(10)->addHour(), 'statut' => 'planifie']);
        $article = Article::factory()->create(['statut' => 'publie', 'publie_le' => now()->subDay()]);
        Article::factory()->brouillon()->create();

        Http::fakeSequence()
            ->push(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode(['answer' => 'Événement proposé.', 'event_ids' => [$event->id]])]]]])
            ->push(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode(['answer' => 'Article résumé.', 'article_ids' => [$article->id]])]]]]);

        $this->actingAs($member)->post(route('ai.front.send', 'evenements'), ['question' => 'Quels événements à venir correspondent à mes intérêts ?'])->assertRedirect();
        $this->post(route('ai.front.send', 'blog'), ['question' => 'Résume un article publié sur l’énergie solaire.', 'article_id' => $article->id])->assertRedirect();
        Http::assertSentCount(2);
        $this->assertAllRequestsUseTheConfiguredGemmaModel();
        $this->assertDatabaseCount('ai_conversations', 2);
    }

    public function test_admin_analytics_use_aggregates_and_do_not_include_user_identity(): void
    {
        $admin = User::factory()->admin()->create();
        $privateUser = User::factory()->create(['name' => 'SYNTHETIC_PRIVATE_NAME']);
        Equipement::factory()->create(['user_id' => $privateUser->id, 'disponible' => true]);
        Http::fake(['openrouter.ai/*' => Http::response(['choices' => [[
            'finish_reason' => 'stop',
            'message' => ['content' => json_encode(['summary' => 'Le catalogue contient des équipements.', 'insights' => ['Des équipements sont disponibles.'], 'recommendations' => ['Examiner les matériels sans réservation.']])],
        ]]])]);

        $this->actingAs($admin)->post(route('ai.admin.send', 'equipements'), ['question' => 'Quels matériels sont peu utilisés ?'])->assertRedirect();
        Http::assertSent(function ($request) {
            $json = $request['messages'][1]['content'];

            return $request['model'] === 'google/gemma-4-31b-it:free'
                && str_contains($json, 'equipements_disponibles')
                && ! str_contains($json, 'SYNTHETIC_PRIVATE_NAME');
        });
    }

    public function test_remaining_admin_modules_send_module_specific_real_aggregates(): void
    {
        $admin = User::factory()->admin()->create();
        $response = ['summary' => 'Les indicateurs montrent une activité à examiner.', 'insights' => ['Une tendance ressort des données agrégées.'], 'recommendations' => ['Examiner cette tendance avant toute décision.']];
        Http::fakeSequence('openrouter.ai/*')
            ->push(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode($response)]]]])
            ->push(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode($response)]]]])
            ->push(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode($response)]]]])
            ->push(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode($response)]]]]);

        foreach (['locations', 'reclamations', 'evenements', 'blog'] as $module) {
            $this->actingAs($admin)->post(route('ai.admin.send', $module), ['question' => 'Résume les indicateurs actuels de ce module.'])->assertRedirect();
        }

        Http::assertSentCount(4);
        $this->assertAllRequestsUseTheConfiguredGemmaModel();
        $contexts = Http::recorded()->map(fn ($pair) => json_decode($pair[0]['messages'][1]['content'], true))->all();
        foreach (['indicateurs_reservations', 'indicateurs_reclamations', 'indicateurs_evenements', 'indicateurs_blog'] as $key) {
            $this->assertTrue(collect($contexts)->contains(fn ($context) => array_key_exists($key, $context)));
        }
    }

    public function test_conversation_history_is_private_and_can_be_deleted_by_its_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $conversation = AiConversation::create(['user_id' => $owner->id, 'module' => 'blog', 'audience' => 'front', 'title' => 'Blog test']);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Contenu synthétique privé.']);

        $this->actingAs($other)->get(route('ai.front.show', ['module' => 'blog', 'conversation_id' => $conversation->id]))->assertNotFound();
        $this->delete(route('ai.front.destroy', ['module' => 'blog', 'conversation' => $conversation->id]))->assertNotFound();

        $this->actingAs($owner)->delete(route('ai.front.destroy', ['module' => 'blog', 'conversation' => $conversation->id]))->assertRedirect(route('ai.front.show', 'blog'));
        $this->assertDatabaseMissing('ai_conversations', ['id' => $conversation->id]);
        $this->assertDatabaseMissing('ai_messages', ['conversation_id' => $conversation->id]);
    }

    private function assertAllRequestsUseTheConfiguredGemmaModel(): void
    {
        $this->assertTrue(Http::recorded()->every(
            fn ($pair) => $pair[0]['model'] === 'google/gemma-4-31b-it:free'
        ));
    }
}
