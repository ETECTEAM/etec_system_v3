<?php

namespace App\Modules\building\Requests;

use App\Modules\building\Data\BuildingData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBuildingRequest extends FormRequest
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
        $building = $this->route('building');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('buildings', 'name')->ignore($building?->id)],
            'code' => ['nullable', 'string', 'max:255', Rule::unique('buildings', 'code')->ignore($building?->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function toData(): BuildingData
    {
        $validated = $this->validated();

        return new BuildingData(
            name: $validated['name'],
            code: $validated['code'] ?? null,
            address: $validated['address'] ?? null,
            description: $validated['description'] ?? null,
        );
    }
}
