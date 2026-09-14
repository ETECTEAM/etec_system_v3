<?php

namespace App\Modules\Enroll\Requests;

use App\Rules\LatinName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Manual Register" tab — recording an old / already-taught registration by
 * hand. There is no class to join, so course / term / time arrive as plain
 * strings and only the payment figures need to be numeric.
 */
class StoreManualRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255', new LatinName],
            'khmer_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s()]{6,20}$/'],

            'course' => ['required', 'string', 'max:255'],
            'term' => ['nullable', 'string', 'max:100'],
            'time' => ['nullable', 'string', 'max:100'],

            'amount_paid' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'document_price' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
