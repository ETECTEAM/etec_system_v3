<?php

namespace App\Modules\User\Requests;

use App\Modules\User\Data\UpdateUserData;
use App\Modules\User\Services\UserService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $targetUser = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@etec\.com$/',
                Rule::unique('users', 'email')->ignore($targetUser?->id),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in($roles)],
        ];
    }

    public function toData(): UpdateUserData
    {
        $validated = $this->validated();

        return new UpdateUserData(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'] ?? null,
            role: $validated['role'],
        );
    }
}
