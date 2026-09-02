<?php

namespace App\Modules\Attendance\Requests;

use App\Modules\Attendance\Queries\GetShortestClassDurationMinutes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAttendanceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'auto_record_enabled' => ['required', 'boolean'],
            'auto_record_grace_minutes' => ['required', 'integer', 'min:1'],
            // Never 'absent' - a student must not fail because an instructor forgot.
            'auto_record_default_status' => ['required', Rule::in(['present', 'pending'])],
            'auto_record_notify_instructor' => ['required', 'boolean'],
            'auto_record_allow_override' => ['required', 'boolean'],
            'auto_record_allow_track_anytime' => ['required', 'boolean'],
            'auto_record_allow_qr_attendance' => ['required', 'boolean'],
            'auto_record_override_hours' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $shortest = app(GetShortestClassDurationMinutes::class)->handle();
            $grace = (int) $this->input('auto_record_grace_minutes');

            if ($shortest !== null && $grace >= $shortest) {
                $validator->errors()->add(
                    'auto_record_grace_minutes',
                    "Grace minutes must be less than the shortest configured class duration ({$shortest} minutes) - otherwise that class could end before it's ever due for auto-recording.",
                );
            }
        });
    }
}
