<?php

namespace App\Modules\Website\Actions;

use App\Models\Notification;
use App\Models\StudyClass;
use App\Modules\Notification\Events\NotificationsUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records a public visitor's "request to join" a class (name/gender/phone,
 * no account) as a dashboard notification for super_admin/admin to follow up on.
 */
class RequestClassJoin
{
    public function handle(StudyClass $studyClass, array $data): void
    {
        DB::transaction(function () use ($studyClass, $data): void {
            $studyClass = StudyClass::query()->lockForUpdate()->findOrFail($studyClass->id);

            $activeCount = $studyClass->enrollments()->where('enrollment_status', 'active')->count();

            if ($activeCount >= $studyClass->capacity) {
                throw ValidationException::withMessages([
                    'name' => 'This class is full.',
                ]);
            }

            $genderLabel = ucfirst($data['gender']);

            Notification::create([
                'title' => 'New Class Join Request',
                'message' => "{$data['name']} ({$genderLabel}, {$data['phone']}) wants to join \"{$studyClass->title}\".",
                'type' => 'class_join_request',
            ]);
        });

        NotificationsUpdated::dispatch();
    }
}
