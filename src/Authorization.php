<?php

namespace Eril\Auth;

use RuntimeException;

final class Authorization
{
    private static ?AuthorizationManager $manager = null;

    public static function configure(AuthConfig $config, AuthManager $auth): void
    {
        self::$manager = new AuthorizationManager($config, $auth);
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

    private static function manager(): AuthorizationManager
    {
        if (!self::$manager) {
            throw new RuntimeException('Authorization is not configured.');
        }

        return self::$manager;
    }
}