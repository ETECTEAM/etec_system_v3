<?php

namespace App\Modules\Auth\Requests\Permissions;

use App\Modules\Auth\Data\Permissions\CreatePermissionData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates one permission name before creation.
 */
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
