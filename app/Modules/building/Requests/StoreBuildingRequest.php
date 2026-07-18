<?php

namespace App\Modules\building\Requests;

use App\Modules\building\Data\BuildingData;
use Illuminate\Foundation\Http\FormRequest;

class StoreBuildingRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:buildings,name'],
            'code' => ['nullable', 'string', 'max:255', 'unique:buildings,code'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function toData(): BuildingData
    {
        $validated = $this->validated();

        return new BuildingData(
            name: $validated['name'],
            code: $validated['code'] ?? null,
            description: $validated['description'] ?? null,
        );
    }
}
