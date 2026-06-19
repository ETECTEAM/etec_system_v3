<?php

namespace App\Modules\Floor\Requests;

use App\Modules\Floor\Data\FloorData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFloorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && ($this->user()->hasRole('super_admin') || $this->user()->hasRole('admin') || $this->user()->hasRole('instructor'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $floor = $this->route('floor');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('floors', 'name')->ignore($floor?->id)],
            'code' => ['nullable', 'string', 'max:255', Rule::unique('floors', 'code')->ignore($floor?->id)],
            'level' => ['nullable', 'integer', 'min:-50', 'max:300', Rule::unique('floors', 'level')->ignore($floor?->id)],
        ];
    }

    public function toData(): FloorData
    {
        $validated = $this->validated();

        return new FloorData(
            name: $validated['name'],
            code: $validated['code'] ?? null,
            level: isset($validated['level']) ? (int) $validated['level'] : null,
        );
    }
}
