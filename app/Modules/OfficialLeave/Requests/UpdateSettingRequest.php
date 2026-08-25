<?php

namespace App\Modules\OfficialLeave\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'monthly_permission_quota' => ['required', 'integer', 'min:0', 'max:100'],
            'permissions_per_absence' => ['required', 'integer', 'min:1', 'max:20'],
            'absence_block_threshold' => ['required', 'integer', 'min:1', 'max:50'],
            'qr_token_ttl_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ];
    }
}
