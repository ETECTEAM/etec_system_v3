<?php

namespace App\Modules\Floor\Services;

use App\Models\Floor;
use App\Modules\Floor\Data\FloorData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class FloorService
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Floor::query()->orderByRaw('level is null')->orderBy('level')->orderBy('name');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('level', $search);
            });
        }

        return $query
            ->paginate($perPage)
            ->through(fn (Floor $floor): array => $this->present($floor));
    }

    public function create(FloorData $data): Floor
    {
        return Floor::query()->create($data->attributes());
    }

    public function update(Floor $floor, FloorData $data): Floor
    {
        $floor->update($data->attributes());

        return $floor->fresh();
    }

    public function delete(Floor $floor): void
    {
        $floor->delete();
    }

    /**
     * @return array{id: int, name: string, level: int|null, created_at: string|null, updated_at: string|null}
     */
    public function present(Floor $floor): array
    {
        return [
            'id' => $floor->id,
            'name' => $floor->name,
            'level' => $floor->level,
            'created_at' => $floor->created_at?->toISOString(),
            'updated_at' => $floor->updated_at?->toISOString(),
        ];
    }
}
