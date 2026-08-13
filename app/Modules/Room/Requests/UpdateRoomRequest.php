<?php

namespace App\Modules\Room\Requests;

use App\Modules\Room\Data\UpdateRoomData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $roomId = $this->route('room')?->id;

        return [
            'floor_id' => ['nullable', 'integer'],
            'room_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rooms', 'room_number')->where(function ($query) {
                    $room = $this->route('room');
                    $floorId = $room?->floor_id ?? $this->route('floor')?->id ?? $this->input('floor_id');
                    return $query->where('floor_id', $floorId);
                })->ignore($roomId),
            ],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'string', Rule::in(['available', 'occupied', 'maintenance', 'closed'])],
        ];
    }

    public function toData(): UpdateRoomData
    {
        $validated = $this->validated();

        return new UpdateRoomData(
            floor_id: $validated['floor_id'] ?? null,
            room_number: $validated['room_number'],
            capacity: $validated['capacity'] ?? null,
            status: $validated['status'],
        );
    }
}
