<?php

namespace App\Modules\Enroll\Requests;

use Illuminate\Foundation\Http\FormRequest<String>;
use Illuminate\Validation\Rule;

class UpdateEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enrollment_status' => ['required', Rule::in(['active', 'pending', 'completed', 'cancelled'])],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'payment_status' => ['required', Rule::in(['unpaid', 'partial', 'paid'])],
            'payment_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
