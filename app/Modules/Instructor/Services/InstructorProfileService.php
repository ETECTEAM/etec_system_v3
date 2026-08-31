<?php

namespace App\Modules\Instructor\Services;

use App\Models\InstructorAttachment;
use App\Models\InstructorAvailability;
use App\Models\InstructorData;
use App\Models\WorkSchedule;
// use Illuminate\Http\UploadedFile; // FILE: disabled - not using file uploads
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Storage; // FILE: disabled - not using file uploads

class InstructorProfileService
{
    public function saveProfile(int $userId, array $data): InstructorData
    {
        return DB::transaction(function () use ($userId, $data): InstructorData {
            $instructor = InstructorData::updateOrCreate(
                ['user_id' => $userId],
                [
                    'full_name' => $data['full_name'],
                    'instructor_code' => $data['instructor_code'],
                    'phone' => $data['phone'],
                    'specialization' => $data['specialization'] ?? null,
                    'employment_type' => $data['employment_type'],
                    'work_schedule_id' => $data['work_schedule_id'] ?? null,
                    'available_for_class' => $data['available_for_class'] ?? true,
                    'status' => $data['status'] ?? true,
                    'headline' => $data['headline'] ?? null,
                    'bio' => $data['bio'] ?? null,
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'address' => $data['address'] ?? null,
                    'telegram' => $data['telegram'] ?? null,
                    'linkedin' => $data['linkedin'] ?? null,
                    'github' => $data['github'] ?? null,
                    'portfolio_url' => $data['portfolio_url'] ?? null,
                ],
            );

            $this->generateInstructorAvailabilities($instructor);

            return $instructor->fresh('availabilities');
        });
    }

    /**
     * Regenerate InstructorAvailability rows from the instructor's WorkSchedule.
     *
     * WorkSchedule → WorkScheduleTime records define (day_of_week, time_id).
     * Each WorkScheduleTime links to a Time record whose time_name encodes
     * the start/end range (e.g. "09:00 am - 10:30 am").
     */
    public function generateInstructorAvailabilities(InstructorData $instructor): void
    {
        InstructorAvailability::where('instructor_id', $instructor->id)->delete();

        if (! $instructor->work_schedule_id) {
            return;
        }

        $schedule = WorkSchedule::with(['times.time'])->find($instructor->work_schedule_id);

        if (! $schedule || $schedule->times->isEmpty()) {
            return;
        }

        $availabilities = [];

        // Deduplicate by time_name: if the times table has duplicate entries
        // with the same label but different IDs, collapse them.
        $seen = [];

        foreach ($schedule->times as $wst) {
            $timeName = $wst->time?->time_name ?? '';
            $range = \App\Models\StudyClass::parseTimeRange($timeName);
            $start = $range['start'] ?? null;
            $end = $range['end'] ?? null;

            if ($start === null || $end === null) {
                continue;
            }

            $key = "{$wst->day_of_week}_{$timeName}";
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $availabilities[] = [
                'instructor_id' => $instructor->id,
                'day_of_week' => $wst->day_of_week,
                'employment_type' => $instructor->employment_type,
                'shift_group' => $schedule->code,
                'period' => $this->periodForScheduleTime($schedule, $wst->day_of_week, $start),
                'start_time' => $start,
                'end_time' => $end,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($availabilities)) {
            InstructorAvailability::insert($availabilities);
        }
    }

    /**
     * InstructorAvailability.period is required. Work schedules model their
     * availability as individual time records, so retain the period semantics
     * used by the former shift templates when creating each row.
     */
    private function periodForScheduleTime(WorkSchedule $schedule, int $dayOfWeek, string $start): string
    {
        if (in_array($dayOfWeek, [6, 7], true)) {
            return str_contains($schedule->code, 'weekend_afternoon') ? 'afternoon' : 'morning';
        }

        if (str_contains($schedule->code, 'morning_afternoon')) {
            return 'daytime';
        }

        if ($start >= '17:00') {
            return 'evening';
        }

        return $start >= '12:00' ? 'afternoon' : 'morning';
    }

    // FILE: disabled - not using file uploads
    // public function saveAttachment(int $instructorId, UploadedFile $file, string $type, ?string $title = null, bool $isPrimary = false): InstructorAttachment
    // {
    //     $path = $file->store("instructors/{$instructorId}", 'public');
    //
    //     return InstructorAttachment::create([
    //         'instructor_id' => $instructorId,
    //         'type' => $type,
    //         'title' => $title,
    //         'file_name' => $file->getClientOriginalName(),
    //         'file_path' => $path,
    //         'file_mime' => $file->getMimeType(),
    //         'file_size' => $file->getSize(),
    //         'is_primary' => $isPrimary,
    //     ]);
    // }

    // FILE: disabled - not using file uploads
    // public function replaceAttachment(int $instructorId, UploadedFile $file, string $type, ?string $title = null): InstructorAttachment
    // {
    //     $old = InstructorAttachment::where('instructor_id', $instructorId)
    //         ->where('type', $type)
    //         ->where('is_primary', true)
    //         ->first();
    //
    //     if ($old) {
    //         Storage::disk('public')->delete($old->file_path);
    //         $old->delete();
    //     }
    //
    //     return $this->saveAttachment($instructorId, $file, $type, $title, true);
    // }

    // FILE: disabled - not using file uploads
    // public function deleteAttachment(int $instructorId, string $type): bool
    // {
    //     $attachments = InstructorAttachment::where('instructor_id', $instructorId)
    //         ->where('type', $type)
    //         ->get();
    //
    //     if ($attachments->isEmpty()) {
    //         return false;
    //     }
    //
    //     foreach ($attachments as $attachment) {
    //         Storage::disk('public')->delete($attachment->file_path);
    //         $attachment->delete();
    //     }
    //
    //     return true;
    // }
}
