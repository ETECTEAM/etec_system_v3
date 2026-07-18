<?php

namespace App\Modules\Floor\Data;

/**
 * Carries validated floor input into FloorService.
 */
readonly class FloorData
{
    public function __construct(
        public string $name,
        public ?int $level,
    ) {}

    /**
     * @return array{name: string, level: int|null}
     */
    public function attributes(): array
    {
        return [
            'name' => $this->name,
            'level' => $this->level,
        ];
    }
}
