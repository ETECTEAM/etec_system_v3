<?php

namespace Tests\Unit\Instructor;

use App\Models\Term;
use App\Modules\Instructor\Services\InstructorClassService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllowedTermIdsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_mon_thu_and_sat_sun_are_allowed(): void
    {
        $monThu = Term::query()->firstOrCreate(['term_name' => 'Mon & Thu']);
        $satSun = Term::query()->firstOrCreate(['term_name' => 'Sat & Sun']);
        Term::query()->firstOrCreate(['term_name' => 'Mon & Tue']);
        Term::query()->firstOrCreate(['term_name' => 'Saturday']);

        $allowed = app(InstructorClassService::class)->allowedTermIds();

        $this->assertEqualsCanonicalizing([$monThu->id, $satSun->id], $allowed);
    }

    public function test_there_is_no_limit_when_neither_term_exists(): void
    {
        Term::query()->whereIn('term_name', InstructorClassService::INSTRUCTOR_TERM_NAMES)->delete();
        Term::query()->firstOrCreate(['term_name' => 'Wed & Thu']);

        $this->assertNull(app(InstructorClassService::class)->allowedTermIds());
    }
}
