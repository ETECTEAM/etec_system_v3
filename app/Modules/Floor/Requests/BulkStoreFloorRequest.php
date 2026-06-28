<?php

namespace App\Modules\Floor\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkStoreFloorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && ($this->user()->hasRole('super_admin') || $this->user()->hasRole('admin') || $this->user()->hasRole('instructor'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_name' => ['required', 'string', 'max:255', 'regex:/^(?:[A-Za-z]+|.*?\d+)$/'],
            'total_floors' => ['required', 'integer', 'min:1', 'max:100'],
            'start_level' => ['nullable', 'integer', 'min:-50', 'max:300'],
        ];
    }

    /**
     * @return array{start_name: string, total_floors: int, start_level: int|null}
     */
    public function payload(): array
    {
        $validated = $this->validated();

        return [
            'start_name' => $validated['start_name'],
            'total_floors' => (int) $validated['total_floors'],
            'start_level' => isset($validated['start_level']) ? (int) $validated['start_level'] : null,
        ];
    }
}
