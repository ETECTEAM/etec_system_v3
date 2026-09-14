<?php

namespace App\Modules\AbsenceBlock\Requests;

use App\Models\StudentAttendanceBlock;
use Illuminate\Foundation\Http\FormRequest;

class RejectAbsenceBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reject', StudentAttendanceBlock::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'admin_comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
