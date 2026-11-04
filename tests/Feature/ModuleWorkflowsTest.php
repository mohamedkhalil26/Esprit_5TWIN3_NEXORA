<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Equipement;
use App\Models\Evenement;
use App\Models\Reclamation;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModuleWorkflowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduled_article_cannot_be_read_or_commented_before_publication(): void
    {
        $article = Article::factory()->create(['statut' => 'publie', 'publie_le' => now()->addDay()]);
        $this->get(route('front.blog.show', $article->slug))->assertNotFound();
        $this->actingAs(User::factory()->create())->post(route('front.blog.commenter', $article->slug), ['contenu' => 'Commentaire avant publication'])->assertNotFound();
        $this->assertDatabaseCount('commentaires', 0);
        $this->assertEquals($article->vues, $article->fresh()->vues);
    }

    public function test_public_catalogue_comparison_and_blog_hide_drafts(): void
    {
        $items = Equipement::factory(2)->create(['disponible' => true]);
        $article = Article::factory()->brouillon()->create();
        $this->get(route('front.equipements.index'))->assertOk();
        $this->get(route('tools.compare', ['ids' => $items->pluck('id')->all()]))->assertOk()->assertSee($items[0]->nom)->assertSee($items[1]->nom);
        $this->get(route('tools.compare', ['ids' => [$items[0]->id]]))->assertSessionHasErrors('ids');
        $this->get(route('front.blog.show', $article->slug))->assertNotFound();
    }

    public function test_reservation_price_is_computed_and_conflict_cannot_be_bypassed_by_status(): void
    {
        $user = User::factory()->create();
        $item = Equipement::factory()->create(['disponible' => true, 'prix_jour' => 12]);
        $data = ['equipement_id' => $item->id, 'date_debut' => today()->addDay()->toDateString(), 'date_fin' => today()->addDays(2)->toDateString(), 'montant_total' => 1, 'user_id' => $item->user_id, 'statut' => 'annulee'];
        $this->actingAs($user)->post(route('front.reservations.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $reservation = Reservation::firstOrFail();
        $this->assertEquals(24, $reservation->montant_total);
        $this->assertSame($user->id, $reservation->user_id);
        $this->assertSame('en_attente', $reservation->statut);
        $this->post(route('front.reservations.store'), $data)->assertSessionHasErrors('date_debut');
        $this->assertDatabaseCount('reservations', 1);
        $this->get(route('tools.availability', ['equipement' => $item->id, 'date_debut' => $data['date_debut'], 'date_fin' => $data['date_fin']]))->assertOk()->assertSee('Réservation impossible');
        $this->actingAs(User::factory()->create())->get(route('front.reservations.show', $reservation))->assertForbidden();
    }

    public function test_owner_and_long_duration_cannot_be_reserved(): void
    {
        $user = User::factory()->create();
        $item = Equipement::factory()->create(['user_id' => $user->id, 'disponible' => true]);
        $this->actingAs($user)->post(route('front.reservations.store'), ['equipement_id' => $item->id, 'date_debut' => today()->toDateString(), 'date_fin' => today()->addDays(31)->toDateString()])->assertSessionHasErrors(['equipement_id', 'date_fin']);
    }

    public function test_event_capacity_duplicate_registration_and_re_registration(): void
    {
        $event = Evenement::factory()->create(['statut' => 'planifie', 'date_debut' => now()->addDays(2), 'date_fin' => now()->addDays(2)->addHours(2), 'capacite' => 1]);
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->actingAs($first)->post(route('front.evenements.inscrire', $event))->assertSessionHasNoErrors();
        $this->post(route('front.evenements.inscrire', $event))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('participations', 1);
        $this->actingAs($second)->post(route('front.evenements.inscrire', $event))->assertSessionHasErrors('evenement_id');
        $this->actingAs($first)->delete(route('front.evenements.desinscrire', $event))->assertRedirect();
        $this->actingAs($second)->post(route('front.evenements.inscrire', $event))->assertSessionHasNoErrors();
        $this->assertSame(1, $event->fresh()->nb_inscrits);
    }

    public function test_favorites_are_idempotent_private_and_removable(): void
    {
        $article = Article::factory()->create();
        $owner = User::factory()->create();
        $this->actingAs($owner)->post(route('favorites.store', $article))->assertRedirect();
        $this->post(route('favorites.store', $article))->assertRedirect();
        $this->assertDatabaseCount('article_user', 1);
        $this->get(route('favorites.index'))->assertOk()->assertSee($article->titre);
        $this->actingAs(User::factory()->create())->get(route('favorites.index'))->assertOk()->assertDontSee($article->titre);
        $this->actingAs($owner)->delete(route('favorites.destroy', $article))->assertRedirect();
        $this->assertDatabaseCount('article_user', 0);
    }

    public function test_complaint_history_and_private_attachment_authorization(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('reclamations/test.pdf', 'attachment');
        $user = User::factory()->create();
        $claim = Reclamation::factory()->create(['user_id' => $user->id, 'piece_jointe' => 'reclamations/test.pdf', 'statut' => 'ouverte']);
        $this->actingAs($user)->get(route('reclamations.attachment', $claim))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('reclamations.attachment', $claim))->assertForbidden();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $claim->update(['statut' => 'en_cours']);
        $this->assertDatabaseHas('reclamation_histories', ['reclamation_id' => $claim->id, 'user_id' => $admin->id]);
        $this->get(route('admin.reclamations.show', $claim))->assertOk()->assertSee('Historique du traitement');
        $this->assertSame('ouverte', $claim->historique->first()->changes['statut']['avant']);
    }

    public function test_payment_cannot_exceed_balance(): void
    {
        $reservation = Reservation::factory()->create(['statut' => 'confirmee']);
        $this->actingAs(User::factory()->admin()->create());
        $payload = ['reservation_id' => $reservation->id, 'montant' => $reservation->montant_total + 1, 'methode' => 'carte', 'statut' => 'valide', 'date_paiement' => now()->toDateTimeString()];
        $this->post(route('admin.paiements.store'), $payload)->assertSessionHasErrors('montant');
        $this->assertDatabaseCount('paiements', 0);
    }

    public function test_authenticated_pages_render(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['profile.edit', 'tools.availability', 'favorites.index', 'front.reservations.index', 'front.reclamations.index', 'front.evenements.mes'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->actingAs(User::factory()->admin()->create());
        foreach (['equipements', 'locations', 'reclamations', 'evenements', 'blog'] as $module) {
            $this->get(route('ai.show', $module))->assertOk();
        }
    }
}
