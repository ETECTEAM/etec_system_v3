<?php

namespace App\Modules\Attendance\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class AttendanceQrSubmitted implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public readonly int $studyClassId,
        public readonly array $attendance,
        public readonly ?array $summary = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('attendance.class.'.$this->studyClassId)];
    }

    public function broadcastAs(): string
    {
        return 'attendance.qr-submitted';
    }

    public function broadcastWith(): array
    {
        $payload = [
            'attendance' => $this->attendance,
        ];

        if ($this->summary !== null) {
            $payload['summary'] = $this->summary;
        }

        return $payload;
    }
}
