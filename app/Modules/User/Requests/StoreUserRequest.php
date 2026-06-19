<?php

namespace App\Modules\User\Requests;

use App\Modules\User\Data\StoreUserData;
use App\Modules\User\Services\UserService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates dashboard user creation and builds StoreUserData.
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $service = app(UserService::class);
        $roles = $this->user() ? $service->assignableRolesFor($this->user()) : [];

        // Limit role choices based on the authenticated user's authority.
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'regex:/^[a-zA-Z0-9._%+-]+@etec\.com$/', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in($roles)],
        ];
    }

    public function toData(): StoreUserData
    {
        $validated = $this->validated();

        // Convert form input into a DTO before it reaches the service layer.
        return new StoreUserData(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
            role: $validated['role'],
        );
    }
}
