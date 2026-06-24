<?php

namespace Eril\Auth\Authorization;

use Eril\Auth\Auth\AuthManager;
use Eril\Auth\Configuration\AuthConfig;
use Eril\Auth\Exceptions\AuthorizationException;
use Eril\Auth\Exceptions\ConfigurationException;

final class Authorization
{
    private static ?PermissionResolver $resolver = null;

    public static function configure(AuthConfig $config, AuthManager $auth): void
    {
        self::$resolver = new PermissionResolver($config, $auth);
    }

    public static function can(string $permission): bool
    {
        return self::resolver()->can($permission);
    }

    public static function cannot(string $permission): bool
    {
        return self::resolver()->cannot($permission);
    }

    public static function authorize(string $permission): void
    {
        if (self::cannot($permission)) {
            throw new AuthorizationException("Unauthorized permission [{$permission}].");
        }
    }

    private static function resolver(): PermissionResolver
    {
        if (!self::$resolver) {
            throw new ConfigurationException('Authorization is not configured.');
        }

        return self::$resolver;
    }
}