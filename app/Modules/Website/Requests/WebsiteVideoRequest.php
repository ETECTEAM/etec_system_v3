<?php

namespace App\Modules\Website\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebsiteVideoRequest extends FormRequest
{
    private const MAX_VIDEO_KILOBYTES = 1048576;

    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_featured' => $this->boolean('is_featured'),
            'remove_thumbnail' => $this->boolean('remove_thumbnail'),
            'sort_order' => $this->integer('sort_order'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isCreating = $this->route('video') === null;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'video' => [$isCreating ? 'required' : 'nullable', 'file', 'mimes:mp4,mov,webm,ogg', 'max:'.self::MAX_VIDEO_KILOBYTES],
            'thumbnail' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'duration' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'remove_thumbnail' => ['boolean'],
        ];
    }
}
