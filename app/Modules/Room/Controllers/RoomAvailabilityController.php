<?php

namespace App\Modules\Room\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassType;
use App\Modules\Room\Services\RoomAvailability;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomAvailabilityController extends Controller
{
    public function index(Request $request, RoomAvailability $availability): Response
    {
        $classTypes = ClassType::query()
            ->orderBy('class_type_id')
            ->get(['class_type_id', 'type_name'])
            ->reject(fn (ClassType $type): bool => $type->isOnline())
            ->values();

        // Physical Class is what admins check most, so it is the default tab.
        $selected = $classTypes->firstWhere('class_type_id', (int) $request->query('class_type_id'))
            ?? $classTypes->first(fn (ClassType $type): bool => str_contains(strtolower($type->type_name), 'physical'))
            ?? $classTypes->first();

        return Inertia::render('backend/buildings/Room/RoomAvailability', [
            'classTypes' => $classTypes->map(fn (ClassType $type): array => [
                'id' => $type->class_type_id,
                'name' => $type->type_name,
            ])->all(),
            'classTypeId' => $selected?->class_type_id,
            'overview' => $selected
                ? $availability->overview($selected->class_type_id)
                : ['terms' => [], 'unassigned' => [], 'roomCount' => 0],
        ]);
    }
}
