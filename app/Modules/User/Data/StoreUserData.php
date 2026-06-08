<?php

namespace App\Modules\User\Data;

readonly class StoreUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $role,
    ) {}

    /**
     * @return array{name: string, email: string, password: string}
     */
    public function userAttributes(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}
