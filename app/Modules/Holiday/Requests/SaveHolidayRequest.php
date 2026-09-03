<?php

namespace App\Modules\Holiday\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'dates' => ['nullable', 'array', 'min:1'],
            'dates.*' => ['required', 'date', 'distinct'],
            'start_date' => ['required_without:dates', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
