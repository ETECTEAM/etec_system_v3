<?php

namespace App\Modules\Website\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class NewsRequest extends FormRequest
{
    private const MAX_IMAGES = 6;

    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_featured' => $this->boolean('is_featured'),
            'sort_order' => $this->integer('sort_order'),
            'image_states' => collect($this->input('image_states', []))
                ->map(fn ($isActive) => filter_var($isActive, FILTER_VALIDATE_BOOLEAN))
                ->all(),
            'remove_images' => collect($this->input('remove_images', []))
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
        return [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:20000'],
            'published_at' => ['nullable', 'date'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'images' => ['nullable', 'array', 'max:'.self::MAX_IMAGES],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'image_states' => ['nullable', 'array'],
            'image_states.*' => ['boolean'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer', 'exists:news_images,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $news = $this->route('news');
            $existingCount = $news?->images()->count() ?? 0;
            $removeCount = count($this->input('remove_images', []));
            $newCount = count($this->file('images', []));

            if (($existingCount - $removeCount + $newCount) > self::MAX_IMAGES) {
                $validator->errors()->add('images', 'News can have a maximum of '.self::MAX_IMAGES.' images.');
            }
        });
    }
}
