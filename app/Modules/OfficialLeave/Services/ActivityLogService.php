<?php

namespace App\Modules\OfficialLeave\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class ActivityLogService
{
    public function getLogs(array $filters, int $perPage = 25)
    {
        $query = ActivityLog::with(['user', 'leave.student']);

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date'] . ' 23:59:59');
        }

        return $query->latest()->paginate($perPage);
    }
}
