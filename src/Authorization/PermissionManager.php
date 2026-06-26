<?php

namespace Eril\Auth\Authorization;

use Eril\Auth\Auth\AuthManager;
use Eril\Auth\Configuration\AuthConfig;

final class PermissionManager
{
    public function __construct(
        private readonly AuthConfig $config,
        private readonly AuthManager $auth,
    ) {}

    public function can(string $permission): bool
    {
        $user = $this->auth->user();

        if (!$user) {
            return false;
        }

        $role = $user->role();

        if (!$role) {
            return false;
        }

        foreach ($this->permissionsForRole($role) as $allowed) {
            if ($this->matches($allowed, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function cannot(string $permission): bool
    {
        return !$this->can($permission);
    }

    private function permissionsForRole(string $role): array
    {
        $permissions = $this->config->permissions();

        return $permissions[$role] ?? [];
    }

    private function matches(string $allowed, string $permission): bool
    {
        if ($allowed === '*') {
            return true;
        }

        if ($allowed === $permission) {
            return true;
        }

        if (str_ends_with($allowed, '.*')) {
            $prefix = substr($allowed, 0, -2);

            return str_starts_with($permission, $prefix . '.');
        }

        return false;
    }
}