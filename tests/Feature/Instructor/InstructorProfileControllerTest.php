<?php

namespace Tests\Feature\Instructor;

use App\Models\InstructorData;
use App\Models\InstructorAttachment;
use App\Models\Term;
use App\Models\Time;
use App\Models\User;
use App\Models\WorkSchedule;
use Database\Seeders\Core\AssignPermissionSeeder;
use Database\Seeders\Core\PermissionSeeder;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstructorProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed([PermissionSeeder::class, RoleSeeder::class, AssignPermissionSeeder::class]);
    }

    public function test_multipart_post_method_spoofing_delivers_all_profile_fields_to_the_put_route(): void
    {
        $user = User::factory()->create([
            'email' => 'instructor10@etec.com',
            'status' => 'active',
        ]);
        $user->assignRole('instructor');

        $instructor = InstructorData::create([
            'user_id' => $user->id,
            'full_name' => 'Bopha Kem',
            'instructor_code' => 'ETEC-010',
            'employment_type' => 'part_time',
            'available_for_class' => true,
            'status' => true,
        ]);
        $schedule = WorkSchedule::create([
            'name' => 'Weekend Morning',
            'code' => 'part_time_weekend_morning',
            'is_active' => true,
        ]);
        $term = Term::create(['term_name' => 'Test Term']);
        $time = Time::create([
            'term_id' => $term->id,
            'time_name' => '08:00 AM - 11:00 AM',
        ]);
        $schedule->times()->create([
            'day_of_week' => 6,
            'time_id' => $time->id,
        ]);

        $payload = [
            '_method' => 'put',
            'email' => 'instructor10@etec.com',
            'full_name' => 'Bopha Kem',
            'instructor_code' => 'ETEC-010',
            'employment_type' => 'part_time',
            'phone' => '012 345 678',
            'specialization' => [],
            'work_schedule_id' => (string) $schedule->id,
            'headline' => 'Part-time instructor',
            'bio' => 'Teaches electronics.',
            'date_of_birth' => '1990-01-20',
            'gender' => 'female',
            'address' => 'Phnom Penh',
            'telegram' => '@bopha',
            'linkedin' => 'https://linkedin.com/in/bopha',
            'github' => 'https://github.com/bopha',
            'portfolio_url' => 'https://bopha.example.com',
            'password' => '',
            'profile_photo' => UploadedFile::fake()->image('profile.jpg'),
        ];

        $this->actingAs($user)
            ->post('/dashboard/instructor/profile', $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $user->refresh();
        $instructor->refresh();

        $this->assertSame('instructor10@etec.com', $user->email);
        $this->assertSame('Bopha Kem', $instructor->full_name);
        $this->assertSame('ETEC-010', $instructor->instructor_code);
        $this->assertSame('part_time', $instructor->employment_type);
        $this->assertSame($schedule->id, $instructor->work_schedule_id);
        $this->assertDatabaseHas('instructor_availabilities', [
            'instructor_id' => $instructor->id,
            'day_of_week' => 6,
            'shift_group' => 'part_time_weekend_morning',
            'period' => 'morning',
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
        ]);
        $this->assertSame('012 345 678', $instructor->phone);
        $this->assertSame('Part-time instructor', $instructor->headline);
        $this->assertSame('Teaches electronics.', $instructor->bio);
        $this->assertSame('1990-01-20', $instructor->date_of_birth?->format('Y-m-d'));
        $this->assertSame('female', $instructor->gender);
        $this->assertSame('Phnom Penh', $instructor->address);
        $this->assertSame('@bopha', $instructor->telegram);
        $this->assertSame('https://linkedin.com/in/bopha', $instructor->linkedin);
        $this->assertSame('https://github.com/bopha', $instructor->github);
        $this->assertSame('https://bopha.example.com', $instructor->portfolio_url);
        $this->assertNotNull($instructor->profilePhoto()->first());
    }

    public function test_instructor_attachment_urls_are_public_storage_paths(): void
    {
        $instructor = InstructorData::create([
            'user_id' => User::factory()->create(['status' => 'active'])->id,
            'full_name' => 'Bopha Kem',
            'instructor_code' => 'ETEC-012',
            'employment_type' => 'part_time',
            'available_for_class' => true,
            'status' => true,
        ]);

        $photo = InstructorAttachment::create([
            'instructor_id' => $instructor->id,
            'type' => 'profile_photo',
            'title' => 'Profile Photo',
            'file_name' => 'profile.jpg',
            'file_path' => 'instructors/1/profile.jpg',
            'file_mime' => 'image/jpeg',
            'file_size' => 12345,
            'is_primary' => true,
        ]);

        $cv = InstructorAttachment::create([
            'instructor_id' => $instructor->id,
            'type' => 'cv',
            'title' => 'CV',
            'file_name' => 'cv.pdf',
            'file_path' => 'instructors/1/cv.pdf',
            'file_mime' => 'application/pdf',
            'file_size' => 67890,
            'is_primary' => true,
        ]);

        $this->assertSame('/storage/instructors/1/profile.jpg', $photo->url);
        $this->assertSame('/storage/instructors/1/cv.pdf', $cv->url);
    }

    public function test_instructor_can_delete_profile_photo_and_cv_attachment(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('instructor');

        $instructor = InstructorData::create([
            'user_id' => $user->id,
            'full_name' => 'Bopha Kem',
            'instructor_code' => 'ETEC-013',
            'employment_type' => 'part_time',
            'available_for_class' => true,
            'status' => true,
        ]);

        Storage::disk('public')->put('instructors/'.$instructor->id.'/profile.jpg', 'photo');
        Storage::disk('public')->put('instructors/'.$instructor->id.'/cv.pdf', 'cv');

        InstructorAttachment::create([
            'instructor_id' => $instructor->id,
            'type' => 'profile_photo',
            'title' => 'Profile Photo',
            'file_name' => 'profile.jpg',
            'file_path' => 'instructors/'.$instructor->id.'/profile.jpg',
            'file_mime' => 'image/jpeg',
            'file_size' => 12345,
            'is_primary' => true,
        ]);

        InstructorAttachment::create([
            'instructor_id' => $instructor->id,
            'type' => 'cv',
            'title' => 'CV',
            'file_name' => 'cv.pdf',
            'file_path' => 'instructors/'.$instructor->id.'/cv.pdf',
            'file_mime' => 'application/pdf',
            'file_size' => 67890,
            'is_primary' => true,
        ]);

        $this->actingAs($user)
            ->delete('/dashboard/instructor/profile/attachments/profile-photo')
            ->assertRedirect();

        $this->assertDatabaseMissing('instructor_attachments', [
            'instructor_id' => $instructor->id,
            'type' => 'profile_photo',
        ]);
        Storage::disk('public')->assertMissing('instructors/'.$instructor->id.'/profile.jpg');

        $this->actingAs($user)
            ->delete('/dashboard/instructor/profile/attachments/cv')
            ->assertRedirect();

        $this->assertDatabaseMissing('instructor_attachments', [
            'instructor_id' => $instructor->id,
            'type' => 'cv',
        ]);
        Storage::disk('public')->assertMissing('instructors/'.$instructor->id.'/cv.pdf');
    }

    public function test_part_time_instructor_cannot_save_a_full_time_work_schedule(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('instructor');

        InstructorData::create([
            'user_id' => $user->id,
            'full_name' => 'Bopha Kem',
            'instructor_code' => 'ETEC-011',
            'employment_type' => 'part_time',
            'available_for_class' => true,
            'status' => true,
        ]);
        $schedule = WorkSchedule::create([
            'name' => 'Full Time',
            'code' => 'full_time_morning_afternoon_weekend_morning',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put('/dashboard/instructor/profile', [
                'email' => $user->email,
                'full_name' => 'Bopha Kem',
                'instructor_code' => 'ETEC-011',
                'employment_type' => 'part_time',
                'specialization' => [],
                'work_schedule_id' => $schedule->id,
            ])
            ->assertSessionHasErrors('work_schedule_id');
    }
}
