<?php

namespace Tests\Feature;

use App\Models\Evenement;
use App\Models\EventBlueprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class EventBlueprintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ai.key' => 'test-key', 'ai.model' => 'openrouter/free']);
        Http::preventStrayRequests();
    }

    private function proposal(): array
    {
        return [
            'title' => 'Atelier solaire pour familles',
            'description' => 'Un atelier pratique pour comprendre les usages de l’énergie solaire.',
            'type' => 'atelier',
            'objectives' => ['Comprendre les usages solaires', 'Partager les bonnes pratiques'],
            'target_audience' => 'Familles et étudiants',
            'suggested_venue' => 'À confirmer',
            'suggested_city' => 'Tunis',
            'suggested_start' => 'Période à confirmer',
            'suggested_end' => null,
            'recommended_capacity' => 30,
            'estimated_budget' => 500,
            'programme' => ['Accueil', 'Démonstration'],
            'activities' => ['Démonstration pratique'],
            'equipment' => ['Kit solaire à confirmer'],
            'organisation' => ['Confirmer le lieu'],
        ];
    }

    private function generationInput(): array
    {
        return ['theme' => 'Atelier solaire', 'objective' => 'Sensibiliser aux usages de l’énergie solaire.', 'target_audience' => 'Familles', 'type' => 'atelier', 'duration' => '2 heures', 'participants' => 30, 'tone' => 'professionnel'];
    }

    private function fakeGeneration(array $proposal): void
    {
        Http::fake(['openrouter.ai/*' => Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode($proposal, JSON_UNESCAPED_UNICODE)]]]])]);
    }

    private function saveBlueprint(User $admin): EventBlueprint
    {
        $this->actingAs($admin)->post(route('ai.admin.blueprints.store'), $this->proposal())->assertRedirect();

        return EventBlueprint::sole();
    }

    public function test_admin_can_open_generator_and_generate_a_valid_structured_proposal_without_creating_event(): void
    {
        $admin = User::factory()->admin()->create();
        $this->fakeGeneration($this->proposal());

        foreach (['fr' => 'ltr', 'en' => 'ltr', 'ar' => 'rtl'] as $locale => $direction) {
            $this->withSession(['locale' => $locale])->actingAs($admin)->get(route('ai.admin.blueprints.create'))
                ->assertOk()->assertSee('lang="'.$locale.'" dir="'.$direction.'"', false)
                ->assertSee(Lang::get('Event Blueprint Generator', [], $locale))
                ->assertSee('name="_token"', false);
        }
        $this->post(route('ai.admin.blueprints.generate'), $this->generationInput())
            ->assertOk()->assertSee('Atelier solaire pour familles')->assertSee('Démonstration pratique');

        Http::assertSentCount(1);
        $this->assertDatabaseCount('event_blueprints', 0);
        $this->assertDatabaseCount('evenements', 0);
        $this->assertDatabaseHas('ai_feature_runs', ['feature' => 'event_blueprint', 'status' => 'completed', 'user_id' => $admin->id]);
    }

    public function test_openrouter_errors_are_shown_without_exposing_provider_response_or_secret(): void
    {
        $admin = User::factory()->admin()->create();
        Http::fake(['openrouter.ai/*' => Http::response(['error' => ['message' => 'test-key internal secret']], 503)]);

        $this->actingAs($admin)->post(route('ai.admin.blueprints.generate'), $this->generationInput())
            ->assertRedirect()->assertSessionHas('error', 'Le service IA rencontre une panne temporaire. Réessayez plus tard.');

        $this->assertDatabaseHas('ai_feature_runs', ['feature' => 'event_blueprint', 'status' => 'provider_error', 'error_code' => 'provider_unavailable']);
        $this->assertDatabaseCount('evenements', 0);
    }

    public function test_admin_can_save_update_list_reopen_and_delete_own_draft(): void
    {
        $admin = User::factory()->admin()->create();
        $blueprint = $this->saveBlueprint($admin);

        $this->get(route('ai.admin.blueprints.index'))->assertOk()->assertSee($blueprint->title)->assertSee('Brouillon');
        $this->get(route('ai.admin.blueprints.edit', $blueprint))->assertOk()->assertSee('Un atelier pratique')->assertSee('Comprendre les usages solaires')->assertSee('Partager les bonnes pratiques');

        $updated = [...$this->proposal(), 'title' => 'Atelier solaire modifié', 'programme' => ["Accueil\nDémonstration\nQuestions"]];
        $this->put(route('ai.admin.blueprints.update', $blueprint), $updated)->assertRedirect()->assertSessionHas('success');
        $this->assertSame('Atelier solaire modifié', $blueprint->fresh()->title);
        $this->assertSame(['Accueil', 'Démonstration', 'Questions'], $blueprint->fresh()->proposal_data['programme']);

        $this->delete(route('ai.admin.blueprints.destroy', $blueprint))->assertRedirect(route('ai.admin.blueprints.index'));
        $this->assertDatabaseMissing('event_blueprints', ['id' => $blueprint->id]);
        $this->assertDatabaseCount('evenements', 0);
    }

    public function test_empty_draft_list_offers_a_real_create_action(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('ai.admin.blueprints.index'))
            ->assertOk()
            ->assertSee(__('You have no saved drafts yet.'))
            ->assertSee(__('Create your first blueprint'))
            ->assertSee('href="'.route('ai.admin.blueprints.create').'"', false);
    }

    public function test_event_prefill_uses_draft_fields_and_keeps_dates_and_event_price_for_admin_confirmation(): void
    {
        $admin = User::factory()->admin()->create();
        $blueprint = $this->saveBlueprint($admin);
        $this->actingAs($admin)->get(route('ai.admin.blueprints.prefill', $blueprint))
            ->assertRedirect(route('admin.evenements.create', ['titre' => $blueprint->title, 'description' => $blueprint->proposal_data['description']."\n\nProgramme proposé :\n- Accueil\n- Démonstration", 'type' => 'atelier', 'lieu' => 'À confirmer', 'ville' => 'Tunis', 'capacite' => 30, 'prix' => 0, 'blueprint' => $blueprint->id]));

        $this->get(route('admin.evenements.create', ['titre' => $blueprint->title, 'description' => $blueprint->proposal_data['description'], 'type' => 'atelier', 'lieu' => 'À confirmer', 'ville' => 'Tunis', 'capacite' => 30, 'prix' => 0, 'blueprint' => $blueprint->id]))
            ->assertOk()->assertSee('value="'.$blueprint->title.'"', false)->assertSee('name="prix"', false)->assertSee('value="0.00"', false)
            ->assertSee(Lang::get('The event form is prefilled from a draft. Review suggested details, choose valid dates and confirm by saving.'));

        $this->assertDatabaseCount('evenements', 0);
    }

    public function test_invalid_proposal_is_rejected_and_non_admin_cannot_use_blueprint_routes(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $invalid = [...$this->proposal(), 'type' => 'unknown', 'recommended_capacity' => 501];
        $this->actingAs($admin)->post(route('ai.admin.blueprints.store'), $invalid)->assertSessionHasErrors(['type', 'recommended_capacity']);
        $this->actingAs($member)->get(route('ai.admin.blueprints.index'))->assertForbidden();
        $this->actingAs($member)->post(route('ai.admin.blueprints.store'), $this->proposal())->assertForbidden();
        $this->assertDatabaseCount('event_blueprints', 0);
    }

    public function test_blueprint_cannot_be_read_updated_prefilled_or_deleted_by_another_admin(): void
    {
        $owner = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        $blueprint = $this->saveBlueprint($owner);
        $this->actingAs($other)->get(route('ai.admin.blueprints.edit', $blueprint))->assertNotFound();
        $this->put(route('ai.admin.blueprints.update', $blueprint), $this->proposal())->assertNotFound();
        $this->get(route('ai.admin.blueprints.prefill', $blueprint))->assertNotFound();
        $this->delete(route('ai.admin.blueprints.destroy', $blueprint))->assertNotFound();
        $this->assertDatabaseHas('event_blueprints', ['id' => $blueprint->id, 'user_id' => $owner->id]);
    }

    public function test_event_is_only_created_after_explicit_validated_post(): void
    {
        $admin = User::factory()->admin()->create();
        $blueprint = $this->saveBlueprint($admin);
        $this->actingAs($admin)->get(route('ai.admin.blueprints.prefill', $blueprint));
        $this->assertDatabaseCount('evenements', 0);
        $this->post(route('admin.evenements.store'), [
            'titre' => $blueprint->title, 'description' => $blueprint->proposal_data['description'], 'type' => 'atelier',
            'lieu' => 'Campus', 'ville' => 'Tunis', 'date_debut' => now()->addDays(10)->format('Y-m-d H:i:s'),
            'date_fin' => now()->addDays(10)->addHours(2)->format('Y-m-d H:i:s'), 'capacite' => 30, 'prix' => 0, 'statut' => 'planifie',
        ])->assertRedirect();
        $this->assertDatabaseCount('evenements', 1);
    }

    public function test_blueprint_forms_include_csrf_token(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('ai.admin.blueprints.create'))->assertOk()->assertSee('name="_token"', false);
        $this->assertSame(0, Evenement::count());
    }
}
