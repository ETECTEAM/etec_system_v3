<?php

namespace App\Modules\Notification\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Signals that the dashboard notification feed changed - the client just
 * refetches GET /notifications/data rather than trusting a duplicated payload
 * here, so the REST endpoint stays the single source of truth for shape.
 */
class NotificationsUpdated implements ShouldBroadcastNow
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
