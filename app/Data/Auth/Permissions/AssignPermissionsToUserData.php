<?php

namespace App\Data\Auth\Permissions;

readonly class AssignPermissionsToUserData
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        public array $permissions,
    ) {}
}
