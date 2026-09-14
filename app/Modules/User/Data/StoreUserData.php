<?php

namespace App\Modules\User\Data;

/**
 * Carries validated user creation input into UserService.
 */
readonly class StoreUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $role,
        public string $status,
        // public ?UploadedFile $avatar, // FILE: disabled - not using file uploads
        public array $student,
        public array $instructorData,
    ) {}

    /**
     * Returns only columns that belong on the users table.
     *
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
