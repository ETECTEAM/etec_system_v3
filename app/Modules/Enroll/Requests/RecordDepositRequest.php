<?php

namespace App\Modules\Enroll\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'deposit_amount' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
