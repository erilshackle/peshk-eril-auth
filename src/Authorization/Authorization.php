<?php

namespace Eril\Auth\Authorization;

use Eril\Auth\Auth\AuthManager;
use Eril\Auth\Configuration\AuthConfig;
use RuntimeException;

final class Authorization
{
    private static ?PermissionResolver $manager = null;

    public static function configure(AuthConfig $config, AuthManager $auth): void
    {
        self::$manager = new PermissionResolver($config, $auth);
    }

    public static function can(string $permission): bool
    {
        return self::manager()->can($permission);
    }

    public static function cannot(string $permission): bool
    {
        return !self::can($permission);
    }

    public static function authorize(string $permission): void
    {
        if (self::cannot($permission)) {
            throw new RuntimeException("Unauthorized permission [{$permission}].");
        }
    }

    private static function manager(): PermissionResolver
    {
        if (!self::$manager) {
            throw new RuntimeException('Authorization is not configured.');
        }

        return self::$manager;
    }
}