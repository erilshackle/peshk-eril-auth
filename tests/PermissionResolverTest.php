<?php

namespace Eril\Auth\Tests;

use Eril\Auth\Auth\AuthManager;
use Eril\Auth\Auth\AuthUser;
use Eril\Auth\Authorization\PermissionResolver;
use Eril\Auth\Configuration\AuthConfig;
use PHPUnit\Framework\TestCase;

/**
 * 
 * @ignore 
 */
final class PermissionResolverTest extends TestCase
{
    public function test_it_allows_exact_permission(): void
    {
        $manager = $this->managerForRole('patient');

        $this->assertTrue($manager->can('profile.view'));
    }

    public function test_it_allows_wildcard_permission(): void
    {
        $manager = $this->managerForRole('professional');

        $this->assertTrue($manager->can('appointments.create'));
        $this->assertTrue($manager->can('appointments.delete'));
    }

    public function test_it_allows_global_wildcard(): void
    {
        $manager = $this->managerForRole('admin');

        $this->assertTrue($manager->can('anything.delete'));
    }

    public function test_it_denies_missing_permission(): void
    {
        $manager = $this->managerForRole('patient');

        $this->assertFalse($manager->can('admin.access'));
    }

    private function managerForRole(string $role): PermissionResolver
    {
        $config = AuthConfig::fromArray([
            'db' => fn () => null,
            'permissions' => [
                'admin' => ['*'],
                'professional' => ['appointments.*'],
                'patient' => ['profile.view'],
            ],
        ]);

        $auth = $this->createMock(AuthManager::class);

        $auth->method('user')->willReturn(
            new AuthUser(['role' => $role])
        );

        return new PermissionResolver($config, $auth);
    }
}