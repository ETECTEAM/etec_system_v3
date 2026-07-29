<?php

namespace App\Modules\Website\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'hero_is_active' => $this->boolean('hero_is_active'),
            'remove_hero_image' => $this->boolean('remove_hero_image'),
            'hero_image_states' => collect($this->input('hero_image_states', []))
                ->map(fn ($isActive) => filter_var($isActive, FILTER_VALIDATE_BOOLEAN))
                ->all(),
            'remove_hero_images' => collect($this->input('remove_hero_images', []))
                ->filter(fn ($remove) => filter_var($remove, FILTER_VALIDATE_BOOLEAN))
                ->keys()
                ->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $pageId = $this->route('page')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'hero_is_active' => ['boolean'],
            'hero_title' => ['nullable', 'string', 'max:255'],
            'hero_subtitle' => ['nullable', 'string', 'max:255'],
            'hero_description' => ['nullable', 'string', 'max:1000'],
            'hero_background_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'hero_slider_images' => ['nullable', 'array', 'max:3'],
            'hero_slider_images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'hero_image_states' => ['nullable', 'array'],
            'hero_image_states.*' => ['boolean'],
            'remove_hero_images' => ['nullable', 'array'],
            'remove_hero_images.*' => ['integer', 'exists:page_hero_images,id'],
            'remove_hero_image' => ['boolean'],
            'primary_button_text' => ['nullable', 'string', 'max:100'],
            'primary_button_url' => ['nullable', 'required_with:primary_button_text', 'string', 'max:255'],
            'secondary_button_text' => ['nullable', 'string', 'max:100'],
            'secondary_button_url' => ['nullable', 'required_with:secondary_button_text', 'string', 'max:255'],
            'overlay_opacity' => ['required', 'integer', 'min:0', 'max:100'],
            'text_alignment' => ['required', Rule::in(['left', 'center', 'right'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $page = $this->route('page');
            $existingCount = $page?->hero?->images()->count() ?? 0;
            $removeCount = count($this->input('remove_hero_images', []));
            $newCount = count($this->file('hero_slider_images', []));

            if (($existingCount - $removeCount + $newCount) > 3) {
                $validator->errors()->add('hero_slider_images', 'Hero slider can have a maximum of 3 images.');
            }
        });
    }
}
