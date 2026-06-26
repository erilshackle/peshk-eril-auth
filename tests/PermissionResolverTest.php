<?php

namespace Eril\Auth\Tests;

use Eril\Auth\Auth\AuthManager;
use Eril\Auth\Authorization\PermissionManager;
use Eril\Auth\Configuration\AuthConfig;
use Eril\Auth\Database\ConnectionResolver;
use Eril\Auth\Session\SessionManager;
use PDO;
use PHPUnit\Framework\TestCase;

final class PermissionResolverTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function test_it_allows_exact_permission(): void
    {
        $resolver = $this->resolverForRole('patient');

        $this->assertTrue($resolver->can('profile.view'));
    }

    public function test_it_allows_wildcard_permission(): void
    {
        $resolver = $this->resolverForRole('professional');

        $this->assertTrue($resolver->can('appointments.create'));
        $this->assertTrue($resolver->can('appointments.delete'));
    }

    public function test_it_allows_global_wildcard(): void
    {
        $resolver = $this->resolverForRole('admin');

        $this->assertTrue($resolver->can('anything.delete'));
    }

    public function test_it_denies_missing_permission(): void
    {
        $resolver = $this->resolverForRole('patient');

        $this->assertFalse($resolver->can('admin.access'));
    }

    public function test_it_denies_when_user_has_no_role(): void
    {
        $resolver = $this->resolverForRole(null);

        $this->assertFalse($resolver->can('profile.view'));
    }

    private function resolverForRole(?string $role): PermissionManager
    {
        $config = AuthConfig::fromArray([
            'db' => new PDO('sqlite::memory:'),

            'permissions' => [
                'admin' => ['*'],
                'professional' => ['appointments.*'],
                'patient' => ['profile.view'],
            ],
        ]);

        $auth = new AuthManager(
            config: $config,
            pdo: new ConnectionResolver($config->db()),
            session: new SessionManager(),
        );

        $auth->login([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => $role,
        ]);

        return new PermissionManager($config, $auth);
    }
}