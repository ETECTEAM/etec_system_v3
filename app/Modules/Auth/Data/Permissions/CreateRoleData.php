<?php

namespace App\Modules\Auth\Data\Permissions;

/**
 * Carries one role name and guard for role creation.
 */
readonly class CreateRoleData
{
    public function __construct(
        public string $name,
        public string $guardName,
    ) {}
}
