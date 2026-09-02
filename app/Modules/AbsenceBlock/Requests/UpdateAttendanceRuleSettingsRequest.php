<?php

namespace App\Modules\AbsenceBlock\Requests;

use App\Models\StudentAttendanceBlock;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRuleSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageSettings', StudentAttendanceBlock::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'absence_block_threshold' => ['required', 'integer', 'min:1', 'max:50'],
            'post_approval_limit' => ['required', 'integer', 'min:1', 'max:20'],
            'permission_weekly_limit' => ['required', 'integer', 'min:0', 'max:20'],
            'cycle_anchor_date' => ['required', 'date'],
        ];
    }
}
