<?php

namespace App\Modules\Auth\Data\Permissions;

readonly class AssignPermissionsToUserData
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        public array $permissions,
    ) {}
}
