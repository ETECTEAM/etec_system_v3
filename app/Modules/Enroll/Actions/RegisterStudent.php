<?php

namespace App\Modules\Enroll\Actions;

use App\Modules\Enroll\Services\StudentRegistrationService;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Pre-registers a student with no class assigned yet. They get enrolled into a
 * class later — e.g. via the "Enroll Existing Student" action on a class, or by
 * scanning the class's own QR self-registration code.
 */
class RegisterStudent
{
    public function __construct(private readonly StudentRegistrationService $registrations) {}

    public function handle(array $data): stdClass
    {
        return DB::transaction(fn (): stdClass => $this->registrations->createStudent($data, auth()->id()));
    }
}
