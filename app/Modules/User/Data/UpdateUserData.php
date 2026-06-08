<?php

namespace App\Modules\User\Data;

readonly class UpdateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        public string $role,
    ) {}

    /**
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
