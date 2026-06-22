<?php

namespace App\Modules\building\Data;

readonly class BuildingData
{
    public function __construct(
        public string $name,
        public ?string $code,
        public ?string $address,
        public ?string $description,
    ) {}

    /**
     * @return array{name: string, code: string|null, address: string|null, description: string|null}
     */
    public function attributes(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'description' => $this->description,
        ];
    }
}
