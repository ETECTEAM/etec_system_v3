<?php

namespace App\Http\Requests\Auth\Permissions;

use App\Data\Auth\Permissions\AssignPermissionsToUserData;
use Illuminate\Foundation\Http\FormRequest;

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
