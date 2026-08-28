<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Dashboard notification feed - only super_admin/admin can subscribe, matching
// the same gate used by NotificationController.
Broadcast::channel('admin-notifications', function ($user) {
    return $user->hasRole('super_admin') || $user->hasRole('admin');
});

Broadcast::channel('attendance.class.{studyClassId}', function ($user, int $studyClassId) {
    if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
        return true;
    }

    return DB::table('study_classes')
        ->where('id', $studyClassId)
        ->where('teacher_id', $user->id)
        ->exists()
        || DB::table('study_class_instructors')
            ->where('study_class_id', $studyClassId)
            ->where('user_id', $user->id)
            ->exists();
});
