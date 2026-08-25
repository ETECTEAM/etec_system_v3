<?php

namespace App\Modules\OfficialLeave\Requests;

use App\Models\OfficialLeave;
use Illuminate\Foundation\Http\FormRequest;

class RevokeLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $leave = $this->route('official_leave');

        return $leave instanceof OfficialLeave
            && $this->user()->can('revoke', $leave);
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
