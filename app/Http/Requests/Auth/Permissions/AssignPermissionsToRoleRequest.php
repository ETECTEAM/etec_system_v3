<?php

namespace App\Http\Requests\Auth\Permissions;

use App\Data\Auth\Permissions\AssignPermissionsToRoleData;
use Illuminate\Foundation\Http\FormRequest;

class AssignPermissionsToRoleRequest extends FormRequest
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
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'exists:permissions,name'],
        ];
    }

    public function toData(): AssignPermissionsToRoleData
    {
        $validated = $this->validated();

        return new AssignPermissionsToRoleData(
            roleName: $validated['role_name'],
            permissions: $validated['permissions'],
        );
    }
}
