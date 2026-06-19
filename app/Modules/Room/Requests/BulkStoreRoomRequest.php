<?php

namespace App\Modules\Room\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkStoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'floor_id' => ['nullable', 'integer', 'exists:floors,id'],
            'start_room_number' => ['required', 'string', 'max:255', 'regex:/^(.*?)(\d+)$/'],
            'total_rooms' => ['required', 'integer', 'min:1', 'max:200'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'string', Rule::in(['available', 'occupied', 'maintenance'])],
        ];
    }

    /**
     * @return array{floor_id: int|null, start_room_number: string, total_rooms: int, capacity: int|null, status: string}
     */
    public function payload(): array
    {
        $validated = $this->validated();

        return [
            'floor_id' => isset($validated['floor_id']) ? (int) $validated['floor_id'] : null,
            'start_room_number' => $validated['start_room_number'],
            'total_rooms' => (int) $validated['total_rooms'],
            'capacity' => isset($validated['capacity']) ? (int) $validated['capacity'] : null,
            'status' => $validated['status'],
        ];
    }
}
