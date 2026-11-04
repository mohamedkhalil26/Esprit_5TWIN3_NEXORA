<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Equipement;
use App\Models\Reclamation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiGlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_search_returns_only_public_resources(): void
    {
        $article = Article::factory()->create(['titre' => 'Article solaire public', 'statut' => 'publie', 'publie_le' => now()->subMinute()]);
        Article::factory()->brouillon()->create(['titre' => 'Article solaire privé']);
        Equipement::factory()->create(['nom' => 'Panneau solaire public', 'disponible' => true]);

        $this->get(route('ai.search', ['q' => 'solaire']))->assertOk()->assertSee($article->titre)->assertSee('Panneau solaire public')->assertDontSee('Article solaire privé');
    }

    public function test_private_search_is_limited_to_current_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Reclamation::factory()->create(['user_id' => $owner->id, 'sujet' => 'Incident privé solaire']);
        Reclamation::factory()->create(['user_id' => $other->id, 'sujet' => 'Incident autre solaire']);

        $this->actingAs($owner)->get(route('ai.search', ['q' => 'solaire', 'type' => 'reclamations']))->assertOk()->assertSee('Incident privé solaire')->assertDontSee('Incident autre solaire');
    }
}
