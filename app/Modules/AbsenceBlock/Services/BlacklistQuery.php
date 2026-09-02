<?php

namespace App\Modules\AbsenceBlock\Services;

use App\Models\StudentAttendanceBlock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * The blacklist listing (the spec's fetch_blacklist_students): every block with
 * filters for type, status, free-text student/tel search and a blocked_at range.
 */
class BlacklistQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = StudentAttendanceBlock::query()
            ->with([
                'student:id,full_name,phone',
                'course:id,title',
                'studyClass:id,title',
                'approver:id,name',
            ]);

        if (! empty($filters['block_type'])) {
            $query->where('block_type', $filters['block_type']);
        }

        match ($filters['status'] ?? null) {
            'pending' => $query->open(),
            'approved' => $query->where('is_approved', true)->whereNull('rejected_at'),
            'rejected' => $query->whereNotNull('rejected_at'),
            default => null,
        };

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('student_tel', 'like', "%{$search}%")
                    ->orWhereHas('student', fn ($s) => $s->where('full_name', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('blocked_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('blocked_at', '<=', $filters['date_to']);
        }

        return $query->latest('blocked_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (StudentAttendanceBlock $block) => [
                'id' => $block->id,
                'block_type' => $block->block_type,
                'status' => $block->statusLabel(),
                'student' => [
                    'id' => $block->student_id,
                    'full_name' => $block->student?->full_name,
                    'tel' => $block->student_tel,
                ],
                'course' => $block->course?->title,
                'study_class' => $block->studyClass?->title,
                'blocked_at' => $block->blocked_at?->toDateTimeString(),
                'approved_at' => $block->approved_at?->toDateTimeString(),
                'rejected_at' => $block->rejected_at?->toDateTimeString(),
                'approved_by' => $block->approver?->name,
                'admin_comment' => $block->admin_comment,
            ]);
    }
}
