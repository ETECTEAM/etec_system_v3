<?php

namespace App\Modules\Auth\Requests\Permissions;

use App\Modules\Auth\Data\Permissions\AssignRoleToUserData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the role name before assigning a role to a user.
 */
class AssignRoleToUserRequest extends FormRequest
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
            'role_name' => ['required', 'string', 'exists:roles,name'],
        ];
    }

    public function toData(): AssignRoleToUserData
    {
        $validated = $this->validated();

        return new AssignRoleToUserData(
            roleName: $validated['role_name'],
        );
    }
}
