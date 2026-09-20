<?php

namespace Tests\Unit\Room;

use App\Models\Room;
use App\Models\StudyClass;
use App\Modules\Room\Services\RoomAvailability;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Unit\Attendance\Concerns\CreatesAttendanceFixtures;

class RoomAvailabilityTest extends TestCase
{
    use RefreshDatabase;
    use CreatesAttendanceFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function makeRoom(string $status = 'available'): Room
    {
        return Room::query()->create(['room_number' => 'R'.random_int(100, 999), 'capacity' => 12, 'status' => $status]);
    }

    private function classInRoom(Room $room, string $term, string $time, string $status = 'active'): StudyClass
    {
        $class = $this->makeStudyClass(['term' => $this->makeTerm($term), 'time' => $this->makeTime($time), 'status' => $status]);
        $class->update(['room_id' => $room->id]);

        return $class;
    }

    public function test_a_room_no_class_uses_is_free(): void
    {
        $room = $this->makeRoom();
        $term = $this->makeTerm('Mon & Thu');
        $time = $this->makeTime('09:00 AM - 10:30 AM');

        $this->assertNull(app(RoomAvailability::class)->unavailableReason($room->id, $term->id, $time->id));
    }

    public function test_a_room_held_by_an_open_class_at_the_same_term_and_time_is_taken(): void
    {
        $room = $this->makeRoom();
        $class = $this->classInRoom($room, 'Mon & Thu', '09:00 AM - 10:30 AM');

        $reason = app(RoomAvailability::class)->unavailableReason($room->id, $class->term_id, $class->time_id);

        $this->assertStringContainsString('already used', (string) $reason);
    }

    public function test_an_overlapping_time_record_also_counts_as_taken(): void
    {
        $room = $this->makeRoom();
        $class = $this->classInRoom($room, 'Mon & Thu', '09:00 AM - 10:30 AM');
        $longerSlot = $this->makeTime('09:00 AM - 11:00 AM');

        $this->assertNotNull(app(RoomAvailability::class)->unavailableReason($room->id, $class->term_id, $longerSlot->id));
    }

    public function test_back_to_back_slots_do_not_overlap(): void
    {
        $room = $this->makeRoom();
        $class = $this->classInRoom($room, 'Mon & Thu', '09:00 AM - 10:30 AM');
        $nextSlot = $this->makeTime('10:30 AM - 12:00 PM');

        $this->assertNull(app(RoomAvailability::class)->unavailableReason($room->id, $class->term_id, $nextSlot->id));
    }

    public function test_a_different_weekday_pattern_is_free(): void
    {
        $room = $this->makeRoom();
        $class = $this->classInRoom($room, 'Mon & Thu', '09:00 AM - 10:30 AM');
        $weekend = $this->makeTerm('Sat & Sun');

        $this->assertNull(app(RoomAvailability::class)->unavailableReason($room->id, $weekend->id, $class->time_id));
    }

    public function test_an_ended_class_no_longer_holds_its_room(): void
    {
        $room = $this->makeRoom();
        $class = $this->classInRoom($room, 'Mon & Thu', '09:00 AM - 10:30 AM', 'ended');

        $this->assertNull(app(RoomAvailability::class)->unavailableReason($room->id, $class->term_id, $class->time_id));
    }

    public function test_a_class_can_keep_its_own_room_when_it_is_excluded(): void
    {
        $room = $this->makeRoom();
        $class = $this->classInRoom($room, 'Mon & Thu', '09:00 AM - 10:30 AM');

        $this->assertNull(app(RoomAvailability::class)->unavailableReason($room->id, $class->term_id, $class->time_id, $class->id));
    }

    public function test_a_room_under_maintenance_is_never_bookable(): void
    {
        $room = $this->makeRoom('maintenance');
        $term = $this->makeTerm('Mon & Thu');
        $time = $this->makeTime('09:00 AM - 10:30 AM');

        $this->assertNotNull(app(RoomAvailability::class)->unavailableReason($room->id, $term->id, $time->id));
    }
}
