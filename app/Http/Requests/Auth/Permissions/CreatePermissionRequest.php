<?php

namespace App\Http\Requests\Auth\Permissions;

use App\Data\Auth\Permissions\CreatePermissionData;
use Illuminate\Foundation\Http\FormRequest;

class CreatePermissionRequest extends FormRequest
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
            'guard_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toData(): CreatePermissionData
    {
        $validated = $this->validated();

        return new CreatePermissionData(
            name: $validated['name'],
            guardName: $validated['guard_name'] ?? 'sanctum',
        );
    }
}
