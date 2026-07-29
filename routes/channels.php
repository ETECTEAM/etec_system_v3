<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Dashboard notification feed - only super_admin/admin can subscribe, matching
// the same gate used by NotificationController.
Broadcast::channel('admin-notifications', function ($user) {
    return $user->hasRole('super_admin') || $user->hasRole('admin');
});
