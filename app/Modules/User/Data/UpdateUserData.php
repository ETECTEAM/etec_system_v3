<?php

namespace App\Modules\User\Data;

/**
 * Carries validated user update input into UserService.
 */
readonly class UpdateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        public string $role,
    ) {}

    /**
     * Returns users table columns, excluding blank passwords.
     *
     * @return array{name: string, email: string, password?: string}
     */
    public function userAttributes(): array
    {
        $attributes = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password !== null && $this->password !== '') {
            $attributes['password'] = $this->password;
        }

        return $attributes;
    }
}
