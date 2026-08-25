<?php

namespace App\Modules\OfficialLeave\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * The student-facing leave form (scanned QR): date range + reason. Range is capped
 * at the school's max leave length; start may be today or later.
 */
class StorePublicLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxDays = (int) config('official-leave.max_leave_days', 30);

        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $start = $this->date('start_date');
            $end = $this->date('end_date');

            if (! $start || ! $end || ! $start->isValid() || ! $end->isValid()) {
                return;
            }

            if ($start->startOfDay()->lt(Carbon::today()->startOfDay())) {
                $validator->errors()->add('start_date', 'The start date cannot be in the past.');
            }

            $maxDays = (int) config('official-leave.max_leave_days', 30);

            // Inclusive range: [start..end] may span at most max_days calendar days.
            if ($start->startOfDay()->diffInDays($end->startOfDay()) + 1 > $maxDays) {
                $validator->errors()->add('end_date', "A single leave request can cover at most {$maxDays} days.");
            }
        });
    }
}
