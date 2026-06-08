<?php

namespace App\Modules\Auth\Requests\Permissions;

use App\Modules\Auth\Data\Permissions\AssignPermissionsToUserData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates permission names before assigning them directly to a user.
 */
class AssignPermissionsToUserRequest extends FormRequest
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
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'exists:permissions,name'],
        ];
    }

    public function toData(): AssignPermissionsToUserData
    {
        $validated = $this->validated();

        return new AssignPermissionsToUserData(
            permissions: $validated['permissions'],
        );
    }
}
