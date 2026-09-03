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
        // Absence-block tuning (threshold, permission->absence conversion) lives
        // in attendance_rule_settings, not here - see the Absence Blocks module.
        return [
            'monthly_permission_quota' => ['required', 'integer', 'min:0', 'max:100'],
            'qr_token_ttl_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ];
    }
}
