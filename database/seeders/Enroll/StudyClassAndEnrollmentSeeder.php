<?php

namespace Database\Seeders\Enroll;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class StudyClassAndEnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $courseIds = DB::table('courses')->orderBy('id')->pluck('id')->values();
            $lessonIds = DB::table('course_lessons')->orderBy('id')->pluck('id')->values();
            $roomIds = DB::table('rooms')->orderBy('id')->pluck('id')->values();
            $teacherIds = $this->teacherIds();

            if ($courseIds->isEmpty()) {
                throw new RuntimeException('No courses found. Please seed courses first.');
            }

            if ($lessonIds->isEmpty()) {
                throw new RuntimeException('No course lessons found. Please seed course lessons first.');
            }

            if ($roomIds->isEmpty()) {
                throw new RuntimeException('No rooms found. Please seed rooms first.');
            }

            if ($teacherIds->isEmpty()) {
                throw new RuntimeException('No instructor users found. Please seed users first.');
            }

            $students = $this->seedStudents();
            $classes = $this->seedStudyClasses($courseIds, $lessonIds, $roomIds, $teacherIds);

            foreach ($classes as $index => $studyClass) {
                $student = $students[$index];
                $paymentStatus = $index === 0 ? 'paid' : ($index === 1 ? 'partial' : 'unpaid');
                $feeAmount = (float) $studyClass->price;
                $documentFeeAmount = (float) $studyClass->document_price;
                $totalFee = $feeAmount + $documentFeeAmount;
                $amountPaid = match ($paymentStatus) {
                    'paid' => $totalFee,
                    'partial' => round($totalFee / 2, 2),
                    default => 0,
                };

                DB::table('student_enrollments')->updateOrInsert(
                    [
                        'study_class_id' => $studyClass->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'enrollment_status' => 'active',
                        'payment_status' => $paymentStatus,
                        'fee_amount' => $feeAmount,
                        'document_fee_amount' => $documentFeeAmount,
                        'amount_paid' => $amountPaid,
                        'enrolled_at' => now()->subDays(4 - $index),
                        'paid_at' => $amountPaid > 0 ? now()->subDays(3 - $index) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        });

        $this->command?->info('Seeded 4 study classes, 4 students, and 4 student enrollments.');
    }

    private function seedStudents()
    {
        $rows = [
            ['name' => 'Dara Sok', 'gender' => 'male', 'phone' => '010111001'],
            ['name' => 'Thida Chan', 'gender' => 'female', 'phone' => '010111002'],
            ['name' => 'Rithy Vann', 'gender' => 'male', 'phone' => '010111003'],
            ['name' => 'Srey Pich', 'gender' => 'female', 'phone' => '010111004'],
        ];

        $students = collect();

        foreach ($rows as $index => $row) {
            $email = 'test.student.'.($index + 1).'@etec.local';

            DB::table('users')->updateOrInsert(
                ['email' => $email],
                [
                    'name' => $row['name'],
                    'password' => Hash::make('password'),
                    'role' => 'student',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $user = DB::table('users')->where('email', $email)->first();

            $studentPayload = [
                'full_name' => $row['name'],
                'gender' => $row['gender'],
                'phone' => $row['phone'],
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('students', 'user_id')) {
                $studentPayload['user_id'] = $user->id;
            }

            if (Schema::hasColumn('students', 'student_status')) {
                $studentPayload['student_status'] = 'active';
            } elseif (Schema::hasColumn('students', 'status')) {
                $studentPayload['status'] = 'active';
            }

            $studentExists = Schema::hasColumn('students', 'user_id')
                ? DB::table('students')->where('user_id', $user->id)->exists()
                : DB::table('students')->where('phone', $row['phone'])->exists();

            if ($studentExists) {
                $query = DB::table('students');
                Schema::hasColumn('students', 'user_id')
                    ? $query->where('user_id', $user->id)
                    : $query->where('phone', $row['phone']);

                $query->update($studentPayload);
            } else {
                $studentPayload['created_at'] = now();
                DB::table('students')->insert($studentPayload);
            }

            $students->push($user);
        }

        return $students;
    }

    private function seedStudyClasses($courseIds, $lessonIds, $roomIds, $teacherIds)
    {
        $rows = [
            [
                'title' => 'Test Web Design Class',
                'class_type' => 'physical',
                'status' => 'active',
                'study_days' => ['Monday', 'Wednesday'],
                'start_time' => '08:00:00',
                'end_time' => '10:00:00',
                'capacity' => 20,
                'price' => 120,
                'document_price' => 5,
            ],
            [
                'title' => 'Test Laravel Class',
                'class_type' => 'physical',
                'status' => 'active',
                'study_days' => ['Tuesday', 'Thursday'],
                'start_time' => '10:00:00',
                'end_time' => '12:00:00',
                'capacity' => 25,
                'price' => 180,
                'document_price' => 10,
            ],
            [
                'title' => 'Test Java Class',
                'class_type' => 'physical',
                'status' => 'upcoming',
                'study_days' => ['Monday', 'Friday'],
                'start_time' => '13:00:00',
                'end_time' => '15:00:00',
                'capacity' => 30,
                'price' => 150,
                'document_price' => 0,
            ],
            [
                'title' => 'Test Python Online Class',
                'class_type' => 'online',
                'status' => 'active',
                'study_days' => ['Wednesday', 'Saturday'],
                'start_time' => '18:00:00',
                'end_time' => '20:00:00',
                'capacity' => 35,
                'price' => 160,
                'document_price' => 5,
            ],
        ];

        $classes = collect();

        foreach ($rows as $index => $row) {
            DB::table('study_classes')->updateOrInsert(
                ['title' => $row['title']],
                [
                    'course_id' => $courseIds[$index % $courseIds->count()],
                    'lesson_id' => $lessonIds[$index % $lessonIds->count()],
                    'teacher_id' => $teacherIds[$index % $teacherIds->count()],
                    'room_id' => $row['class_type'] === 'online' ? null : $roomIds[$index % $roomIds->count()],
                    'class_type' => $row['class_type'],
                    'status' => $row['status'],
                    'study_days' => json_encode($row['study_days']),
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'capacity' => $row['capacity'],
                    'price' => $row['price'],
                    'document_price' => $row['document_price'],
                    'enrollment_start_date' => now()->subWeek()->toDateString(),
                    'start_date' => now()->addDays($index + 1)->toDateString(),
                    'end_date' => now()->addMonths(3)->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $classes->push(DB::table('study_classes')->where('title', $row['title'])->first());
        }

        return $classes;
    }

    private function teacherIds()
    {
        return DB::table('users')
            ->whereIn('role', ['instructor', 'teacher'])
            ->orWhereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->where('model_has_roles.model_type', 'App\\Models\\User')
                    ->whereIn('roles.name', ['instructor', 'teacher']);
            })
            ->orderBy('id')
            ->pluck('id')
            ->values();
    }
}
