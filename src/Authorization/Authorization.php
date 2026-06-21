<?php

namespace Eril\Auth\Authorization;

use Eril\Auth\Auth\AuthManager;
use Eril\Auth\Configuration\AuthConfig;
use Eril\Auth\Exceptions\AuthorizationException;

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
            throw new AuthorizationException("Unauthorized permission [{$permission}].");
        }
    }

    private static function manager(): PermissionResolver
    {
        if (!self::$manager) {
            throw new AuthorizationException('Authorization is not configured.');
        }

        return self::$manager;
    }
}