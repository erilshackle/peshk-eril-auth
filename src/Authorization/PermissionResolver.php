<?php

namespace Eril\Auth\Authorization;

use Eril\Auth\Auth\AuthManager;
use Eril\Auth\Configuration\AuthConfig;

final class PermissionResolver
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

        $permissions = $this->permissionsForRole($role);

        if (in_array('*', $permissions, true)) {
            return true;
        }

        return in_array($permission, $permissions, true);
    }

    private function permissionsForRole(string $role): array
    {
        $permissions = $this->config->permissions();

        return $permissions[$role] ?? [];
    }
}