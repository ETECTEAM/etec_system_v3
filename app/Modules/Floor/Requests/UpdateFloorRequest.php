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
        $buildingId = $floor?->building_id ?? $this->route('building')?->id ?? $this->input('building_id');

        return [
            'building_id' => ['nullable', 'integer', 'exists:buildings,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('floors', 'name')->where(function ($query) use ($buildingId) {
                    return $query->where('building_id', $buildingId);
                })->ignore($floor?->id),
            ],
            'level' => [
                'nullable',
                'integer',
                'min:-50',
                'max:300',
                Rule::unique('floors', 'level')->where(function ($query) use ($buildingId) {
                    return $query->where('building_id', $buildingId);
                })->ignore($floor?->id),
            ],
        ];
    }

    public function toData(): FloorData
    {
        $validated = $this->validated();

        return new FloorData(
            name: $validated['name'],
            level: isset($validated['level']) ? (int) $validated['level'] : null,
        );
    }
}
