<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ai\Dashboard\AIDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_dashboard_is_available_on_home_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('Mon activité SolarShare')
            ->assertSee('Réservations actives')
            ->assertSee(route('front.reservations.index'))
            ->assertSee(route('front.reclamations.index'))
            ->assertSee(route('front.evenements.mes'));
    }

    public function test_dashboard_rates_use_null_when_no_reservation_exists(): void
    {
        $kpis = app(AIDashboardService::class)->admin();

        $this->assertNull($kpis['cancellation_rate']);
    }

    public function test_admin_insights_page_is_role_protected_and_uses_real_empty_state(): void
    {
        $this->actingAs(User::factory()->create())->get(route('ai.admin.insights.index'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('ai.admin.insights.index'))
            ->assertOk()
            ->assertSee('Aucune réservation enregistrée pour calculer la demande par catégorie.')
            ->assertSee('ai-trend-chart', false)
            ->assertSee('Détail mensuel');
    }

    public function test_insights_period_filter_rebuilds_the_number_of_monthly_chart_columns(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('ai.admin.insights.index', ['months' => 3]))
            ->assertOk()
            ->assertSee('name="months"', false)
            ->assertSee('value="3" selected', false)
            ->assertSee('3 '.__('ai_insights.months'));

        $invalidPeriod = $this->get(route('ai.admin.insights.index', ['months' => 24]))->assertOk()->assertSee('value="6" selected', false);
        $this->assertSame(24, substr_count($invalidPeriod->getContent(), 'class="ai-trend-column"'));
    }

    public function test_admin_dashboard_shows_database_kpis_quick_actions_and_real_activity_chart(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Actions rapides')
            ->assertSee(route('admin.equipements.create'))
            ->assertSee(route('admin.reclamations.intelligence'))
            ->assertSee('Activité réelle — 6 derniers mois')
            ->assertSee('Comparaison mensuelle du nombre de réservations et de réclamations');
    }
}
