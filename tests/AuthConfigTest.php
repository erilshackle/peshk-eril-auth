<?php

namespace Eril\Auth\Tests;

use Eril\Auth\Configuration\AuthConfig;
use Eril\Auth\Exceptions\ConfigurationException;
use PHPUnit\Framework\TestCase;

final class AuthConfigTest extends TestCase
{
    public function test_it_accepts_minimal_configuration(): void
    {
        $config = AuthConfig::fromArray([
            'db' => fn () => null,
        ]);

        $this->assertSame('users', $config->userTable());
        $this->assertSame('email', $config->loginField());
    }

    public function test_it_rejects_missing_database_connection(): void
    {
        $this->expectException(ConfigurationException::class);

        AuthConfig::fromArray([]);
    }

    public function test_it_rejects_invalid_table_name(): void
    {
        $this->expectException(ConfigurationException::class);

        AuthConfig::fromArray([
            'db' => fn () => null,
            'user_table' => 'users; DROP TABLE users',
        ]);
    }

    public function test_it_accepts_profiles_configuration(): void
    {
        $config = AuthConfig::fromArray([
            'db' => fn () => null,
            'profiles' => [
                'patient' => [
                    'table' => 'patients',
                    'foreign_key' => 'user_id',
                ],
            ],
        ]);

        $this->assertSame('patients', $config->profiles()['patient']['table']);
    }
}