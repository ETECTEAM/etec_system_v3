<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// `notifications` collided with Laravel's own Notifiable morph table:
// User uses the Notifiable trait, so `$user->notifications` queries
// notifiable_type/notifiable_id columns that don't exist on this
// hand-rolled table and throws. Renaming frees the conventional table name
// for real per-user Laravel notifications later without touching the
// title/message/is_read data this custom Notification model already reads.
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('notifications', 'dashboard_notifications');
    }

    public function down(): void
    {
        Schema::rename('dashboard_notifications', 'notifications');
    }
};
