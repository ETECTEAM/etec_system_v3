<?php

namespace App\Modules\Auth\Data\Permissions;

/**
 * Carries the role name and permission names for role permission sync.
 */
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
