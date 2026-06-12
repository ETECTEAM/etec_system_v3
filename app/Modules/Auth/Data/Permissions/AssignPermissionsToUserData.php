<?php

namespace App\Modules\Auth\Data\Permissions;

/**
 * Carries permission names for assigning direct permissions to a user.
 */
readonly class AssignPermissionsToUserData
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        public array $permissions,
    ) {}
}
