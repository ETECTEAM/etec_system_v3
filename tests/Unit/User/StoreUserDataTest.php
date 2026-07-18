<?php

namespace Tests\Unit\User;

use App\Modules\User\Data\StoreUserData;
use PHPUnit\Framework\TestCase;

class StoreUserDataTest extends TestCase
{
    public function test_user_attributes_returns_only_the_users_table_columns(): void
    {
        $data = new StoreUserData(
            name: 'Jane Doe',
            email: 'jane@etec.com',
            password: 'hashed-password',
            role: 'instructor',
            status: 'active',
            avatar: null,
            student: [],
            instructorData: ['full_name' => 'Jane Doe'],
        );

        $this->assertSame([
            'name' => 'Jane Doe',
            'email' => 'jane@etec.com',
            'password' => 'hashed-password',
        ], $data->userAttributes());
    }
}
