<?php

namespace App\Modules\Enroll\Actions;

use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;

class UpdatePublicRegistrationDetails
{
    public function handle(StudentEnrollment $enrollment, array $data): StudentEnrollment
    {
        DB::transaction(function () use ($enrollment, $data): void {
            $enrollment->loadMissing('student.student');

            $enrollment->student?->forceFill(['name' => $data['name']])->save();

            $enrollment->student?->student?->forceFill([
                'full_name' => $data['name'],
                'gender' => $data['gender'],
                'phone' => $data['phone'],
            ])->save();
        });

        return $enrollment->refresh();
    }
}
