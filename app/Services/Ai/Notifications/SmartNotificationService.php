<?php

namespace App\Services\Ai\Notifications;

use App\Models\NotificationPreference;
use App\Models\SmartNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class SmartNotificationService
{
    public function create(User $user, string $category, string $title, string $content, string $dedupeKey, ?Model $resource = null, string $priority = 'normal'): ?SmartNotification
    {
        $preferences = NotificationPreference::where('user_id', $user->id)->first() ?: new NotificationPreference(['enabled' => true, 'categories' => null]);
        $categories = $preferences->categories ?? [];
        if (! $preferences->enabled || (($categories[$category] ?? true) === false)) {
            return null;
        }
        if (SmartNotification::where('dedupe_key', $dedupeKey)->exists()) {
            return SmartNotification::where('dedupe_key', $dedupeKey)->first();
        }

        return SmartNotification::create([
            'user_id' => $user->id,
            'category' => $category,
            'priority' => in_array($priority, ['low', 'normal', 'high', 'urgent'], true) ? $priority : 'normal',
            'status' => 'pending',
            'title' => $title,
            'content' => $content,
            'resource_type' => $resource?->getMorphClass(),
            'resource_id' => $resource?->getKey(),
            'dedupe_key' => $dedupeKey,
        ]);
    }
}
