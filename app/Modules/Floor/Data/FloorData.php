<?php

namespace App\Modules\Floor\Data;

/**
 * Carries validated floor input into FloorService.
 */
readonly class FloorData
{
    public function __construct(
        public ?int $building_id,
        public string $name,
        public ?string $code,
        public ?int $level,
    ) {}

    /**
     * @return array{building_id: int|null, name: string, code: string|null, level: int|null}
     */
    public function attributes(): array
    {
        return [
            'building_id' => $this->building_id,
            'name' => $this->name,
            'code' => $this->code,
            'level' => $this->level,
        ];
    }
}
