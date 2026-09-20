<?php

namespace Tests\Unit\Account;

use App\Models\User;
use App\Modules\Account\Services\RecoveryEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecoveryEmailUniquenessTest extends TestCase
{
    use RefreshDatabase;

    private function service(): RecoveryEmailService
    {
        return app(RecoveryEmailService::class);
    }

    public function test_gmail_dots_plus_tags_and_the_googlemail_alias_are_one_inbox(): void
    {
        $canonical = RecoveryEmailService::canonical('ab@gmail.com');

        $this->assertSame($canonical, RecoveryEmailService::canonical('A.B@Gmail.com'));
        $this->assertSame($canonical, RecoveryEmailService::canonical('a.b+school@googlemail.com'));
        $this->assertNotSame(RecoveryEmailService::canonical('a.b@yahoo.com'), RecoveryEmailService::canonical('ab@yahoo.com'));
    }

    public function test_an_address_verified_by_another_account_is_taken_even_with_dots_or_tags(): void
    {
        User::factory()->create(['recovery_email' => 'sokha.dev@gmail.com', 'recovery_verified' => true]);
        $me = User::factory()->create();

        $this->assertTrue($this->service()->takenByAnotherAccount('sokhadev+x@gmail.com', $me->id));
    }

    public function test_an_unverified_recovery_email_held_by_someone_else_does_not_block(): void
    {
        User::factory()->create(['recovery_email' => 'sokha.dev@gmail.com', 'recovery_verified' => false]);
        $me = User::factory()->create();

        $this->assertFalse($this->service()->takenByAnotherAccount('sokha.dev@gmail.com', $me->id));
    }

    public function test_another_accounts_login_email_is_taken(): void
    {
        User::factory()->create(['email' => 'boss@gmail.com']);
        $me = User::factory()->create();

        $this->assertTrue($this->service()->takenByAnotherAccount('b.oss@gmail.com', $me->id));
    }

    public function test_an_account_can_keep_its_own_verified_recovery_email(): void
    {
        $me = User::factory()->create(['recovery_email' => 'me.recover@gmail.com', 'recovery_verified' => true]);

        $this->assertFalse($this->service()->takenByAnotherAccount('me.recover@gmail.com', $me->id));
    }
}
