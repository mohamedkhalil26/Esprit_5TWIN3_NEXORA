<?php

namespace Tests\Feature;

use App\Models\NotificationPreference;
use App\Models\SmartNotification;
use App\Models\User;
use App\Services\Ai\Notifications\SmartNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_are_deduplicated_and_preferences_are_respected(): void
    {
        $user = User::factory()->create();
        $service = app(SmartNotificationService::class);
        $first = $service->create($user, 'event', 'Événement proche', 'Rappel synthétique.', 'event:1:reminder:2030-01-01');
        $second = $service->create($user, 'event', 'Événement proche', 'Rappel synthétique.', 'event:1:reminder:2030-01-01');

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('smart_notifications', 1);

        NotificationPreference::create(['user_id' => $user->id, 'enabled' => true, 'categories' => ['event' => false]]);
        $this->assertNull($service->create($user, 'event', 'Autre', 'Ignorée.', 'event:2:reminder:2030-01-01'));
    }

    public function test_notification_read_action_is_private(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $notification = SmartNotification::create(['user_id' => $owner->id, 'category' => 'event', 'title' => 'Titre', 'content' => 'Contenu', 'dedupe_key' => 'unique:1']);

        $this->actingAs($other)->patch(route('notifications.read', $notification))->assertNotFound();
        $this->actingAs($owner)->patch(route('notifications.read', $notification))->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
    }
}
