<?php

namespace App\Modules\User\Data;

use Illuminate\Http\UploadedFile;

readonly class UpdateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        public string $role,
        public bool $status,
        public ?UploadedFile $avatar,
        public array $student,
        public array $instructorData,
    ) {}
}
