<?php

namespace App\Modules\Auth\Data\Permissions;

readonly class AssignRoleToUserData
{
    public function __construct(
        public string $roleName,
    ) {}
}
