<?php

namespace App\Data\Auth\Permissions;

readonly class AssignPermissionsToRoleData
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        public string $roleName,
        public array $permissions,
    ) {}
}
