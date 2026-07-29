<?php

namespace App\Modules\Website\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'parent_id' => $this->input('parent_id') ?: null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $parentRules = [
            'nullable',
            'integer',
            Rule::exists('menus', 'id'),
        ];

        if ($this->route('menu')) {
            $parentRules[] = Rule::notIn([$this->route('menu')->id]);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => $parentRules,
            'page_id' => ['required', 'integer', 'exists:pages,id'],
            'position' => ['sometimes', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }
}
