<?php

namespace App\Modules\Floor\Data;

/**
 * Carries validated floor input into FloorService.
 */
readonly class FloorData
{
    public function __construct(
        public string $name,
        public ?string $code,
        public ?int $level,
    ) {}

    /**
     * @return array{name: string, code: string|null, level: int|null}
     */
    public function attributes(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'level' => $this->level,
        ];
    }
}
