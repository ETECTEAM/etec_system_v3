<?php

namespace App\Modules\Auth\Data\Permissions;

/**
 * Carries the role name that should be assigned to a user.
 */
readonly class AssignRoleToUserData
{
    public function __construct(
        public string $roleName,
    ) {}
}
