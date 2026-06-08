<?php

namespace App\Http\Requests\Auth\Permissions;

use App\Data\Auth\Permissions\CreateFeaturePermissionsData;
use Illuminate\Foundation\Http\FormRequest;

class CreateFeaturePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'actions' => ['nullable', 'array', 'min:1'],
            'actions.*' => ['required', 'string', 'max:255'],
            'guard_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toData(): CreateFeaturePermissionsData
    {
        $validated = $this->validated();

        return new CreateFeaturePermissionsData(
            name: $validated['name'],
            actions: $validated['actions'] ?? ['view', 'create', 'update', 'delete'],
            guardName: $validated['guard_name'] ?? 'sanctum',
        );
    }
}
