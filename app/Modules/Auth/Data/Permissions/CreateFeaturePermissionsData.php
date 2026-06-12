<?php

namespace App\Modules\Auth\Data\Permissions;

/**
 * Carries a feature name and action list for generating permissions.
 */
readonly class CreateFeaturePermissionsData
{
    /**
     * @param  array<int, string>  $actions
     */
    public function __construct(
        public string $name,
        public array $actions,
        public string $guardName,
    ) {}
}
