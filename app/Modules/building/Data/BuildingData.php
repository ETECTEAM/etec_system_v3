<?php

namespace App\Modules\building\Data;

readonly class BuildingData
{
    public function __construct(
        public string $name,
        public ?string $code,
        public ?string $description,
    ) {}

    /**
     * @return array{name: string, code: string|null, description: string|null}
     */
    public function attributes(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
        ];
    }
}
