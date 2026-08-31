<?php

namespace App\Modules\AbsenceBlock\Requests;

use App\Models\AttendanceRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAttendanceRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', AttendanceRule::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'rule_type' => ['required', Rule::in([AttendanceRule::TYPE_ABSENCE, AttendanceRule::TYPE_PERMISSION])],
            'limit_count' => ['required', 'integer', 'min:1', 'max:100'],
            'period_type' => ['required', Rule::in([AttendanceRule::PERIOD_WEEK, AttendanceRule::PERIOD_MONTH, AttendanceRule::PERIOD_BOTH])],
            'start_date' => ['required', 'date'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
