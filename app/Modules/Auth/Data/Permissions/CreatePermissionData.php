<?php

namespace App\Modules\Auth\Data\Permissions;

/**
 * Carries one permission name and guard for permission creation.
 */
readonly class CreatePermissionData
{
    public function __construct(
        public string $name,
        public string $guardName,
    ) {}
}
