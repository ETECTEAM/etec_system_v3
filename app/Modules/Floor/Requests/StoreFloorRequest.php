<?php

namespace App\Modules\Floor\Requests;

use App\Modules\Floor\Data\FloorData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFloorRequest extends FormRequest
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
        return [
            'building_id' => ['nullable', 'integer', 'exists:buildings,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('floors', 'name')->where(fn ($query) => $query->where('building_id', $this->input('building_id'))),
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('floors', 'code')->where(fn ($query) => $query->where('building_id', $this->input('building_id'))),
            ],
            'level' => [
                'nullable',
                'integer',
                'min:-50',
                'max:300',
                Rule::unique('floors', 'level')->where(fn ($query) => $query->where('building_id', $this->input('building_id'))),
            ],
        ];
    }

    public function toData(): FloorData
    {
        $validated = $this->validated();

        return new FloorData(
            building_id: isset($validated['building_id']) ? (int) $validated['building_id'] : null,
            name: $validated['name'],
            code: $validated['code'] ?? null,
            level: isset($validated['level']) ? (int) $validated['level'] : null,
        );
    }
}
