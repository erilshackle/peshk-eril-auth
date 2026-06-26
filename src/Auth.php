<?php

namespace Eril\Auth;

use Eril\Auth\Auth\AuthManager;
use Eril\Auth\Auth\AuthUser;
use Eril\Auth\Authorization\Authorization;
use Eril\Auth\Configuration\AuthConfig;
use Eril\Auth\Configuration\ConfigPublisher;
use Eril\Auth\Database\ConnectionResolver;
use Eril\Auth\Exceptions\ConfigurationException;
use Eril\Auth\Profile\Profile;
use Eril\Auth\Session\SessionManager;

final class Auth
{
    private static ?AuthManager $manager = null;

    /**
     * Configure the authentication system.
     *
     * This method should usually be called once during application bootstrap.
     *
     * @param array{
     *     db:\PDO|callable,
     *     user_table?:string,
     *     id_field?:string,
     *     name_field?:string,
     *     login_field?:string|array<int,string>,
     *     password_field?:string,
     *     role_field?:string|null,
     *     profiles?:array<string,array{table:string, foreign_key:string}>,
     *     permissions?:array<string,array<int,string>>,
     *     rate_limit?:array{
     *         enabled?:bool,
     *         max_attempts?:int,
     *         decay_seconds?:int,
     *         key?:string
     *     },
     *     providers?:array{
     *         table:string,
     *         provider_field:string,
     *         provider_id_field:string,
     *         user_id_field:string
     *     },
     *     session_name?:string,
     *     session_lifetime?:int|null,
     *     remember_enabled?:bool,
     *     remember_cookie?:string,
     *     remember_token_field?:string|null,
     *     remember_selector_field?:string|null,
     *     remember_lifetime?:int
     * } $config
     * 
     *
     * @throws ConfigurationException
     */
    public static function configure(array $config): void
    {
        $authConfig = AuthConfig::fromArray($config);
        $pdo = new ConnectionResolver($authConfig->db());

        self::$manager = new AuthManager(
            config: $authConfig,
            pdo: $pdo,
            session: new SessionManager(),
        );

        Authorization::configure($authConfig, self::$manager);

        self::$manager->boot();
    }

    /**
     * Load authentication configuration from a PHP file.
     *
     * The file must return an array accepted by Auth::configure().
     *
     * If $createIfMissing is true and the file does not exist, a default
     * auth.php file will be created. When the generated auth.php references
     * permissions.php, that file will also be created in the same directory.
     *
     * Example:
     *
     * Auth::loadConfig(__DIR__ . '/config/auth.php');
     *
     * Auth::loadConfig(
     *     __DIR__ . '/config/auth.php',
     *     createIfMissing: true
     * );
     *
     * @throws ConfigurationException
     */
    public static function loadConfig(
        string $path,
        bool $createIfMissing = false
    ): void {
        if (!is_file($path)) {
            if (!$createIfMissing) {
                throw new ConfigurationException("Auth config file not found: {$path}");
            }

            ConfigPublisher::publish($path);
        }

        $config = require $path;

        if (!is_array($config)) {
            throw new ConfigurationException('Auth config file must return an array.');
        }

        self::configure($config);
    }

    /**
     * Attempt to authenticate a user using login and password.
     *
     * The login value is matched against the configured login_field.
     *
     * @return AuthUser|false
     */
    public static function attempt(string $login, string $password): AuthUser|false
    {
        return self::manager()->attempt($login, $password);
    }

    /**
     * Manually login a user using a database row or compatible array.
     * @param array $user
     */
    public static function login(array $user): AuthUser
    {
        return self::manager()->login($user);
    }

    /**
     * Login using an already validated external provider identity.
     *
     * This method does not perform OAuth validation.
     * The provider identity must be validated by the application before calling it.
     */
    public static function loginWithProvider(string $provider, string $providerId): AuthUser|false
    {
        return self::manager()->loginWithProvider($provider, $providerId);
    }

    /**
     * Logout the current authenticated user.
     */
    public static function logout(): void
    {
        self::manager()->logout();
    }

    /**
     * Determine if there is an authenticated user.
     */
    public static function check(): bool
    {
        return self::manager()->check();
    }

    /**
     * Determine if there is no user authenticated.
     */
    public static function guest(): bool
    {
        return !self::check();
    }

    /**
     * Get the current authenticated user.
     */
    public static function user(): ?AuthUser
    {
        return self::manager()->user();
    }

    /**
     * Get the profile associated with the authenticated user's role.
     *
     * Profiles are loaded on demand using the configured "profiles" map.
     */
    public static function profile(): ?Profile
    {
        return self::manager()->profile();
    }

    /**
     * Get the current authenticated user id.
     */
    public static function id(): int|string|null
    {
        return self::manager()->id();
    }

    /**
     * Determine if the authenticated user has one of the given roles.
     */
    public static function hasRole(string $role, string ...$roles): bool
    {
        return self::manager()->hasRole($role, ...$roles);
    }

    /**
     * Determine if the authenticated user has the given role.
     */
    public static function is(string $role): bool
    {
        return self::hasRole($role);
    }

    /**
     * Determine if the authenticated user has the given permission.
     */
    public static function can(string $permission): bool
    {
        return Authorization::can($permission);
    }

    /**
     * Determine if the authenticated user does not have the given permission.
     */
    public static function cannot(string $permission): bool
    {
        return Authorization::cannot($permission);
    }

    /**
     * Require the authenticated user to have the given permission.
     *
     * @throws \Eril\Auth\Exceptions\AuthorizationException
     */
    public static function authorize(string $permission): void
    {
        Authorization::authorize($permission);
    }

    /**
     * Remember the current authenticated user using a persistent cookie.
     */
    public static function rememberUser(): void
    {
        self::manager()->rememberUser();
    }

    /**
     * Get the last authentication error.
     */
    public static function error(): ?string
    {
        return self::manager()->error();
    }

    /**
     * Diagnose current authentication configuration and database schema.
     *
     * Auth must be configured before calling this method.
     *
     * @return array<string, array{ok:bool, message:string}>
     */
    public static function diagnose(): array
    {
        return self::manager()->diagnose();
    }

    /**
     * Get the configured PDO connection.
     * @internal description
     */
    public static function connection(): \PDO
    {
        return self::manager()->connection();
    }

    private static function manager(): AuthManager
    {
        if (!self::$manager) {
            throw new ConfigurationException('Auth is not configured. Call Auth::configure() first.');
        }

        return self::$manager;
    }
}
