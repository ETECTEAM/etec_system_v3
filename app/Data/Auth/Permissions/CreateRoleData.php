<?php

namespace App\Data\Auth\Permissions;

readonly class CreateRoleData
{
    public function __construct(
        public string $name,
        public string $guardName,
    ) {}
}
