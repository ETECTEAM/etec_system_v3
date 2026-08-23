<?php

namespace App\Modules\Notification\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Signals that the dashboard notification feed changed - the client just
 * refetches GET /notifications/data rather than trusting a duplicated payload
 * here, so the REST endpoint stays the single source of truth for shape.
 *
 * Queued (not ShouldBroadcastNow) so a broadcast-layer failure (e.g. Reverb
 * unreachable) fails a background job instead of the triggering HTTP request.
 */
class NotificationsUpdated implements ShouldBroadcast
{
    use Dispatchable;

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin-notifications')];
    }

    public function broadcastAs(): string
    {
        return 'notifications.updated';
    }
}
