<?php

namespace App\Modules\Auth\Data\Permissions;

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
