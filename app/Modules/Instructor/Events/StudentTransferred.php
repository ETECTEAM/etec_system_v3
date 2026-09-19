<?php

namespace App\Modules\Instructor\Events;

use App\Models\StudyClass;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class StudentTransferred implements ShouldBroadcastNow
{
    use Dispatchable;

    /** @param list<int> $recipientUserIds */
    public function __construct(
        private readonly array $recipientUserIds,
        private readonly int $studyClassId,
        private readonly string $classTitle,
        private readonly string $studentName,
    ) {}

    public static function forClass(StudyClass $studyClass, string $studentName): self
    {
        $studyClass->loadMissing('instructors:id');

        $recipientUserIds = array_values(array_unique(array_filter([
            $studyClass->teacher_id,
            ...$studyClass->instructors->pluck('id')->all(),
        ])));

        return new self($recipientUserIds, $studyClass->id, $studyClass->title, $studentName);
    }

    public function broadcastOn(): array
    {
        return array_map(
            fn (int $userId) => new PrivateChannel('instructor-notifications.'.$userId),
            $this->recipientUserIds,
        );
    }

    public function broadcastAs(): string
    {
        return 'student.transferred';
    }

    public function broadcastWith(): array
    {
        return [
            'study_class_id' => $this->studyClassId,
            'class_title' => $this->classTitle,
            'student_name' => $this->studentName,
        ];
    }
}
