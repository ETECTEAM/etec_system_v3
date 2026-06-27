<?php

namespace App\Modules\Room\Requests;

use App\Modules\Room\Data\StoreRoomData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'floor_id' => ['nullable', 'integer'],
            'room_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rooms', 'room_number')->where(function ($query) {
                    return $query->where('floor_id', $this->route('floor')?->id ?? $this->input('floor_id'));
                }),
            ],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'string', Rule::in(['available', 'occupied', 'maintenance'])],
        ];
    }

    public function toData(): StoreRoomData
    {
        $validated = $this->validated();

        return new StoreRoomData(
            floor_id: $validated['floor_id'] ?? null,
            room_number: $validated['room_number'],
            capacity: $validated['capacity'] ?? null,
            status: $validated['status'],
        );
    }
}
