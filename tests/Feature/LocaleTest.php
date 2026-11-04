<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_supported_locale_is_saved_in_session_and_applied_to_layout(): void
    {
        $response = $this->post(route('locale.update'), ['locale' => 'ar']);

        $response->assertRedirect()->assertSessionHas('locale', 'ar');
        $this->get(route('ai.search'))->assertOk()->assertSee('lang="ar" dir="rtl"', false);
    }

    public function test_locale_input_is_restricted_to_supported_languages(): void
    {
        $this->post(route('locale.update'), ['locale' => 'de'])->assertSessionHasErrors('locale');
    }

    public function test_locale_switcher_is_available_in_both_front_and_admin_layouts(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('id="front-locale-select"', false)
            ->assertSee('value="fr"', false)
            ->assertSee('value="ar"', false)
            ->assertSee('value="en"', false);

        $admin = User::factory()->admin()->create();
        $this->withSession(['locale' => 'ar'])->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('id="admin-locale-select"', false)
            ->assertSee('لوحة المعلومات');
    }

    public function test_language_change_applies_to_the_following_request_and_carbon_dates(): void
    {
        $this->from(route('home'))->post(route('locale.update'), ['locale' => 'en'])
            ->assertRedirect(route('home'))
            ->assertSessionHas('locale', 'en')
            ->assertSessionHas('success', 'Language updated.');

        $this->assertSame('en', Carbon::getLocale());
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('lang="en" dir="ltr"', false)
            ->assertSee('Share the power of')
            ->assertSee('How does it work?');
    }

    public function test_authenticated_locale_preference_is_persisted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('locale.update'), ['locale' => 'en'])->assertRedirect();
        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_ai_hub_is_polished_and_localized_in_french_english_and_arabic(): void
    {
        $admin = User::factory()->admin()->create();
        foreach ([
            'fr' => ['ltr', 'SolarMatch', 'Complaint Doctor', 'Event Matchmaker', 'Assistant Blog'],
            'en' => ['ltr', 'SolarMatch', 'Complaint Doctor', 'Event Matchmaker', 'Blog Assistant'],
            'ar' => ['rtl', 'SolarMatch', 'Complaint Doctor', 'Event Matchmaker', 'مساعد المدونة'],
        ] as $locale => [$direction, $solarTitle, $doctorTitle, $eventTitle, $blogTitle]) {
            $response = $this->withSession(['locale' => $locale])->actingAs($admin)->get(route('ai.index'));
            $response
                ->assertOk()->assertSee('lang="'.$locale.'" dir="'.$direction.'"', false)
                ->assertSee($solarTitle)->assertSee('Smart Booking Optimizer')
                ->assertSee($doctorTitle)->assertSee($eventTitle)->assertSee($blogTitle);
            $hubContent = Str::between($response->getContent(), '<section class="ai-hub-page', '</section>');
            $this->assertStringNotContainsString('Front Office', $hubContent);
            $this->assertStringNotContainsString('Back Office', $hubContent);
            $this->assertStringNotContainsString('Equipment Demand Radar', $hubContent);
            $this->assertStringNotContainsString('Booking Insights', $hubContent);

            $this->get(route('home'))
                ->assertOk()
                ->assertSee(__('ai_hub.home_title'))
                ->assertSee($solarTitle);
        }
    }
}
