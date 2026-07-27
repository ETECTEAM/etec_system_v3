<?php

namespace App\Modules\Website\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'page_id' => ['required', 'integer', 'exists:pages,id'],
            'position' => ['sometimes', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }
}
