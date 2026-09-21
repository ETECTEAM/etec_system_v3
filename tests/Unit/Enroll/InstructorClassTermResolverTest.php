<?php

namespace Tests\Unit\Enroll;

use App\Models\ClassType;
use App\Models\Course;
use App\Models\InstructorAvailability;
use App\Models\InstructorData;
use App\Models\Schedule;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use App\Models\User;
use App\Modules\Enroll\Queries\GetClassFormOptions;
use App\Modules\Enroll\Services\InstructorAssignmentAvailability;
use App\Modules\Enroll\Services\InstructorClassTermResolver;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * A two-part term name is a weekday range (StudyClass::parseTermDays), so "Mon & Thu" is
 * Mon-Thu and a Collapse Class share splits it into Mon & Tue for the owner and Wed & Thu for
 * the partner. Picking "Mon & Thu" again then leaves Monday and Tuesday taken with Wednesday
 * and Thursday open; the resolver moves that create onto Wed & Thu instead of refusing it.
 */
class InstructorClassTermResolverTest extends TestCase
{
    use RefreshDatabase;

    private InstructorData $instructor;

    private ClassType $basic;

    private Time $time;

    /** @var array<string, Term> */
    private array $terms = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('instructor');

        $this->instructor = InstructorData::create([
            'user_id' => $user->id,
            'full_name' => 'Collapsing Instructor',
            'available_for_class' => true,
            'status' => true,
        ]);

        // Free all week 08:00-12:00, so anything that blocks a day in these tests
        // is the collapse itself rather than a missing availability window.
        foreach (range(1, 7) as $day) {
            InstructorAvailability::create([
                'instructor_id' => $this->instructor->id,
                'day_of_week' => $day,
                'employment_type' => 'full_time',
                'shift_group' => 'custom',
                'period' => 'daytime',
                'start_time' => '08:00',
                'end_time' => '12:00',
                'is_active' => true,
            ]);
        }

        $this->basic = ClassType::create(['type_name' => 'Basic']);
        $this->time = Time::create(['time_name' => '09:00 AM - 10:30 AM']);

        foreach (['Mon & Thu', 'Mon & Tue', 'Wed & Thu', 'Sat & Sun', 'Wednesday'] as $name) {
            $this->terms[$name] = Term::create(['term_name' => $name]);
        }
    }

    private function scheduleTerms(string ...$names): void
    {
        foreach ($names as $name) {
            Schedule::create([
                'class_type_id' => $this->basic->class_type_id,
                'term_id' => $this->terms[$name]->id,
            ])->times()->attach($this->time->id);
        }
    }

    /** Creates a class the instructor teaches, optionally collapsed onto $ownerTerm. */
    private function classFor(string $term, ?string $ownerTerm = null): StudyClass
    {
        $course = Course::create([
            'title' => 'Existing '.$term,
            'slug' => str($term)->slug()->toString().'-'.uniqid(),
            'status' => 'active',
        ]);

        $class = StudyClass::create([
            'title' => $course->title,
            'course_id' => $course->id,
            'teacher_id' => $this->instructor->user_id,
            'term_id' => $this->terms[$term]->id,
            'time_id' => $this->time->id,
            'status' => 'upcoming',
            'capacity' => 12,
            'price' => 0,
        ]);

        if ($ownerTerm !== null) {
            // What Collapse Class writes: the owner's own half lives on the pivot,
            // the class keeps its original term_id.
            $class->instructors()->attach([
                $this->instructor->user_id => [
                    'term_id' => $this->terms[$ownerTerm]->id,
                    'time_id' => $this->time->id,
                ],
            ]);
        }

        return $class;
    }

    private function resolve(string $term): array
    {
        return app(InstructorClassTermResolver::class)->resolve(
            $this->instructor->user_id,
            $this->terms[$term]->id,
            $this->time->id,
            $this->basic->class_type_id,
        );
    }

    public function test_it_keeps_the_picked_term_when_every_day_is_free(): void
    {
        $this->scheduleTerms('Mon & Thu', 'Wed & Thu');

        $placement = $this->resolve('Mon & Thu');

        $this->assertSame($this->terms['Mon & Thu']->id, $placement['term_id']);
        $this->assertNull($placement['moved_from']);
    }

    // The scenario this exists for: collapsed onto Mon & Tue, picks Mon & Thu (Mon-Thu),
    // and the class lands on Wed & Thu - exactly the half of the range still open.
    public function test_it_moves_the_class_onto_a_free_term_when_one_picked_day_is_taken(): void
    {
        $this->scheduleTerms('Mon & Thu', 'Wed & Thu', 'Sat & Sun');
        $this->classFor('Mon & Thu', ownerTerm: 'Mon & Tue');

        $placement = $this->resolve('Mon & Thu');

        $this->assertSame($this->terms['Wed & Thu']->id, $placement['term_id']);
        $this->assertSame('Wed & Thu', $placement['term_name']);
        $this->assertSame('Mon & Thu', $placement['moved_from']);
        $this->assertSame(['Monday', 'Tuesday'], $placement['taken_days']);
    }

    // Sat & Sun shares no day with the Mon-Thu pick, so it is not a replacement for it
    // even though the instructor is completely free then.
    public function test_it_rejects_when_no_configured_term_shares_a_free_picked_day(): void
    {
        $this->scheduleTerms('Mon & Thu', 'Sat & Sun');
        $this->classFor('Mon & Thu', ownerTerm: 'Mon & Tue');

        try {
            $this->resolve('Mon & Thu');
            $this->fail('Expected the create to be rejected.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('You already teach on Monday and Tuesday', $e->errors()['term_id'][0]);
            $this->assertStringContainsString('no other schedule is free', $e->errors()['term_id'][0]);
        }
    }

    // Moving off one conflict and onto another is no help. Thursday is still free here, so
    // there is something to salvage, but the only term offering it - Wed & Thu - also needs a
    // Wednesday the instructor no longer has.
    public function test_it_rejects_when_the_only_candidate_term_is_itself_partly_taken(): void
    {
        $this->scheduleTerms('Mon & Thu', 'Wed & Thu');
        $this->classFor('Mon & Thu', ownerTerm: 'Mon & Tue');
        $this->classFor('Wed & Thu', ownerTerm: 'Wednesday');

        try {
            $this->resolve('Mon & Thu');
            $this->fail('Expected the create to be rejected.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('no other schedule is free', $e->errors()['term_id'][0]);
        }
    }

    // Nothing left to salvage - the earlier class covers the whole Mon-Thu range - so the
    // instructor gets the plain conflict message rather than a "no other schedule" one.
    public function test_it_rejects_with_the_conflict_reason_when_no_picked_day_is_free(): void
    {
        $this->scheduleTerms('Mon & Thu', 'Wed & Thu');
        $this->classFor('Mon & Thu');

        try {
            $this->resolve('Mon & Thu');
            $this->fail('Expected the create to be rejected.');
        } catch (ValidationException $e) {
            $this->assertSame(
                'The selected instructor already has a class at this time.',
                $e->errors()['time_id'][0],
            );
        }
    }

    // The picker has to keep offering the half-free slot, or there is nothing to resolve.
    public function test_the_schedule_picker_still_offers_a_slot_with_one_free_day(): void
    {
        $this->scheduleTerms('Mon & Thu', 'Wed & Thu');
        $this->classFor('Mon & Thu', ownerTerm: 'Mon & Tue');

        $groups = app(InstructorAssignmentAvailability::class)->filterScheduleGroups(
            $this->instructor->user_id,
            app(GetClassFormOptions::class)->scheduleGroups(),
        );

        $termNames = collect($groups)->flatMap(fn (array $group) => collect($group['schedules'])->pluck('term_name'))->all();

        $this->assertContains('Mon & Thu', $termNames);
    }

    // ... but a slot with no free day at all still disappears from the picker.
    public function test_the_schedule_picker_drops_a_slot_with_no_free_day(): void
    {
        $this->scheduleTerms('Mon & Thu');
        $this->classFor('Mon & Thu');

        $groups = app(InstructorAssignmentAvailability::class)->filterScheduleGroups(
            $this->instructor->user_id,
            app(GetClassFormOptions::class)->scheduleGroups(),
        );

        $this->assertSame([], $groups);
    }
}
