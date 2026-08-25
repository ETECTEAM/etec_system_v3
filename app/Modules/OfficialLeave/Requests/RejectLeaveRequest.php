<?php

namespace App\Modules\OfficialLeave\Requests;

use App\Models\OfficialLeave;
use Illuminate\Foundation\Http\FormRequest;

class RejectLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $leave = $this->route('official_leave');

        return $leave instanceof OfficialLeave
            && $this->user()->can('reject', $leave);
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'A short rejection note is required.',
        ];
    }
}
