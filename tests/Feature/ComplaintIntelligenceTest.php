<?php

namespace Tests\Feature;

use App\Models\Reclamation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ComplaintIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_doctor_returns_suggestions_without_submitting_the_complaint(): void
    {
        config(['ai.key' => 'test-key', 'ai.model' => 'test-model']);
        $content = json_encode([
            'improved_subject' => 'Batterie hors service',
            'improved_description' => 'La batterie ne fonctionne plus depuis ce matin.',
            'category' => 'equipement',
            'priority' => 'haute',
            'summary' => 'La batterie est en panne.',
            'missing_information' => ['Préciser le modèle'],
            'explanation' => 'La catégorie correspond à la panne décrite.',
        ], JSON_UNESCAPED_UNICODE);
        Http::fake(['openrouter.ai/*' => Http::response([
            'choices' => [['finish_reason' => 'stop', 'message' => ['content' => $content]]],
        ])]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('front.reclamations.improve'), [
            'sujet' => 'Batterie cassée', 'description' => 'La batterie ne fonctionne plus depuis ce matin.',
            'categorie' => 'autre', 'priorite' => 'moyenne',
        ]);

        $response->assertOk()->assertJsonPath('suggestions.category', 'equipement');
        $this->assertDatabaseCount('reclamations', 0);
        $this->assertDatabaseHas('ai_feature_runs', ['feature' => 'complaint_doctor', 'status' => 'completed']);
    }

    public function test_admin_center_uses_database_aggregates_and_shows_unknown_resolution_time_without_history(): void
    {
        $admin = User::factory()->admin()->create();
        Reclamation::factory()->create(['categorie' => 'technique', 'priorite' => 'haute', 'statut' => 'ouverte']);
        Reclamation::factory()->create(['categorie' => 'paiement', 'priorite' => 'moyenne', 'statut' => 'resolue']);

        $this->actingAs($admin)->get(route('admin.reclamations.intelligence'))
            ->assertOk()->assertSee('Centre d’intelligence des réclamations')->assertSee('2')->assertSee('Données insuffisantes');
    }

    public function test_non_admin_cannot_access_complaint_intelligence_center(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.reclamations.intelligence'))->assertForbidden();
    }

    public function test_front_complaint_doctor_ui_respects_french_english_and_arabic_locales(): void
    {
        $user = User::factory()->create();
        foreach ([
            'fr' => ['Assistant de rédaction de réclamation', 'ltr'],
            'en' => ['Complaint writing assistant', 'ltr'],
            'ar' => ['مساعد صياغة الشكوى', 'rtl'],
        ] as $locale => [$title, $direction]) {
            $this->withSession(['locale' => $locale])->actingAs($user)
                ->get(route('front.reclamations.create'))
                ->assertOk()->assertSee('lang="'.$locale.'" dir="'.$direction.'"', false)->assertSee($title);
        }
    }
}
