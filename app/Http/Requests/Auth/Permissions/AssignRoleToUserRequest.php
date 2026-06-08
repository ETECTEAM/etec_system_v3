<?php

namespace App\Http\Requests\Auth\Permissions;

use App\Data\Auth\Permissions\AssignRoleToUserData;
use Illuminate\Foundation\Http\FormRequest;

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
