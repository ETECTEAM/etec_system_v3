<?php

namespace Database\Seeders\Core;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\ScheduleClass;
use App\Models\Enrollment;
use App\Models\Time;
use Faker\Factory as Faker;

class RegistrationDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        $times = Time::all();
        if ($times->isEmpty()) {
            $times = collect([(object)['id' => 1]]); // Fallback
        }

        // Generate 5 Classes
        $classes = [];
        for ($i = 1; $i <= 5; $i++) {
            $classes[] = ScheduleClass::create([
                'class_name' => $faker->randomElement(['Web Development', 'Mobile App Design', 'Network Security', 'Python Programming', 'Data Science Foundation']) . ' - Batch ' . $faker->numberBetween(1, 10),
                'time_id' => $times->random()->id,
                'capacity' => $faker->numberBetween(20, 30),
            ]);
        }

        // Generate 20 Students and Enrollments
        for ($i = 0; $i < 20; $i++) {
            $student = Student::create([
                'full_name' => $faker->name,
                'gender' => $faker->randomElement(['male', 'female']),
                'date_of_birth' => $faker->dateTimeBetween('-30 years', '-15 years')->format('Y-m-d'),
                'phone' => '0' . $faker->numberBetween(10000000, 99999999),
                'address' => $faker->address,
                'status' => 'active',
            ]);

            // Assign student to a random class
            $randomClass = $faker->randomElement($classes);

            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $randomClass->id,
                'status' => 'active',
                'enrollment_date' => $faker->dateTimeBetween('-1 months', 'now')->format('Y-m-d'),
                'note' => $faker->sentence(),
            ]);
        }
    }
}
