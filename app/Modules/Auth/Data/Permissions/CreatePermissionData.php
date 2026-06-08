<?php

namespace App\Modules\Auth\Data\Permissions;

readonly class CreatePermissionData
{
    public function __construct(
        public string $name,
        public string $guardName,
    ) {}
}
