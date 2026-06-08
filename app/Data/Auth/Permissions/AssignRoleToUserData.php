<?php

namespace App\Data\Auth\Permissions;

readonly class AssignRoleToUserData
{
    public function __construct(
        public string $roleName,
    ) {}
}
