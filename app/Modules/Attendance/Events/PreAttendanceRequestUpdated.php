<?php

namespace App\Modules\Attendance\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class PreAttendanceRequestUpdated implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public readonly int $studyClassId,
        public readonly int $requestId,
        public readonly string $status,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('attendance.class.'.$this->studyClassId)];
    }

    public function broadcastAs(): string
    {
        return 'pre-attendance.request-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'study_class_id' => $this->studyClassId,
            'request_id' => $this->requestId,
            'status' => $this->status,
            'status_label' => ucfirst(str_replace('_', ' ', $this->status)),
        ];
    }
}
