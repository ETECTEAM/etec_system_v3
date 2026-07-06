<?php

namespace Tests\Unit\User;

use App\Modules\User\Data\UpdateUserData;
use PHPUnit\Framework\TestCase;

class UpdateUserDataTest extends TestCase
{
    public function test_user_attributes_omits_password_when_left_blank(): void
    {
        $data = new UpdateUserData(
            name: 'Jane Doe',
            email: 'jane@etec.com',
            password: null,
            role: 'instructor',
            status: 'active',
            avatar: null,
            student: [],
            instructorData: [],
        );

        $this->assertSame([
            'name' => 'Jane Doe',
            'email' => 'jane@etec.com',
        ], $data->userAttributes());
    }

    public function test_user_attributes_omits_password_when_an_empty_string(): void
    {
        $data = new UpdateUserData(
            name: 'Jane Doe',
            email: 'jane@etec.com',
            password: '',
            role: 'instructor',
            status: 'active',
            avatar: null,
            student: [],
            instructorData: [],
        );

        $this->assertArrayNotHasKey('password', $data->userAttributes());
    }

    public function test_user_attributes_includes_password_when_present(): void
    {
        $data = new UpdateUserData(
            name: 'Jane Doe',
            email: 'jane@etec.com',
            password: 'new-hashed-password',
            role: 'instructor',
            status: 'active',
            avatar: null,
            student: [],
            instructorData: [],
        );

        $this->assertSame([
            'name' => 'Jane Doe',
            'email' => 'jane@etec.com',
            'password' => 'new-hashed-password',
        ], $data->userAttributes());
    }
}
