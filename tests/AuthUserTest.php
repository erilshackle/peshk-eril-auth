<?php

namespace Eril\Auth\Tests;

use Eril\Auth\Auth\AuthUser;
use PHPUnit\Framework\TestCase;

final class AuthUserTest extends TestCase
{
    public function test_it_returns_normalized_user_data(): void
    {
        $user = new AuthUser([
            'id' => 1,
            'name' => 'Eril',
            'login' => 'eril@example.com',
            'role' => 'admin',
            'raw' => [
                'email' => 'eril@example.com',
                'phone' => '123',
            ],
        ]);

        $this->assertSame(1, $user->id());
        $this->assertSame('Eril', $user->name());
        $this->assertSame('eril@example.com', $user->login());
        $this->assertSame('admin', $user->role());
    }

    public function test_it_checks_roles(): void
    {
        $user = new AuthUser([
            'role' => 'admin',
        ]);

        $this->assertTrue($user->is('admin'));
        $this->assertTrue($user->hasRole('user', 'admin'));
        $this->assertFalse($user->is('patient'));
    }

    public function test_json_serialization_hides_raw_data(): void
    {
        $user = new AuthUser([
            'id' => 1,
            'raw' => [
                'password' => 'secret',
            ],
        ]);

        $this->assertSame(['id' => 1], $user->jsonSerialize());
    }
}