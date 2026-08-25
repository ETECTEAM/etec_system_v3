<?php

namespace App\Modules\OfficialLeave\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The four system-wide official-leave settings. Bounds come from the seeded
 * rows' min/max so the Settings page, the request, and the table stay in sync.
 */
class UpdateOfficialLeaveSettingsRequest extends FormRequest
{
    private const FIELDS = [
        'monthly_permission_quota',
        'permissions_per_absence',
        'absence_block_threshold',
        'qr_token_ttl_minutes',
    ];

    public function authorize(): bool
    {
        return $this->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        $rules = [];

        foreach (self::FIELDS as $field) {
            $rules[$field] = ['required', 'integer', 'min:0', 'max:10000'];
        }

        return $rules;
    }

    public static function settingKeys(): array
    {
        return self::FIELDS;
    }
}
