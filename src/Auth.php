<?php

namespace Eril\Auth;

final class Auth
{
    private static ?AuthManager $manager = null;

    /**
     * Configure authentication.
     *
     * Example:
     *
     * Auth::configure([
     *     'db' => $pdo,
     *     'user_table' => 'users',
     *     'id_field' => 'id',
     *     'name_field' => 'name',
     *     'login_field' => 'email',
     *     'password_field' => 'password',
     *     'role_field' => 'role',
     *     'session_name' => 'auth_user',
     *     'session_lifetime' => 3600,
     *     'remember_enabled' => true,
     *     'remember_cookie' => 'remember_token',
     *     'remember_token_field' => 'remember_token',
     *     'remember_days' => 30,
     * ]);
     *
     * Available options:
     *
     * @param array{
     *     db:\PDO|callable,
     *     user_table?:string,
     *     id_field?:string,
     *     name_field?:string,
     *     login_field?:string,
     *     password_field?:string,
     *     role_field?:string|null,
     *     session_name?:string,
     *     session_lifetime?:int,
     *     remember_enabled?:bool,
     *     remember_cookie?:string,
     *     remember_token_field?:string|null,
     *     remember_days?:int
     * } $config
     *
     * db:
     *      PDO instance or callable returning PDO.
     *
     * user_table:
     *      User table name.
     *
     * id_field:
     *      Primary key column.
     *
     * name_field:
     *      Display name column.
     *
     * login_field:
     *      Column used for authentication
     *      (email, username, phone, etc.).
     *
     * password_field:
     *      Password hash column.
     *
     * role_field:
     *      User role column.
     *      Set null to disable role checks.
     *
     * session_name:
     *      Session key used to store authenticated user.
     *
     * session_lifetime:
     *      Session lifetime in seconds.
     *
     * remember_enabled:
     *      Enable remember-me functionality.
     *
     * remember_cookie:
     *      Remember-me cookie name.
     *
     * remember_token_field:
     *      Database column used to store token hash.
     *
     * remember_days:
     *      Cookie lifetime in days.
     */
    public static function configure(array $config): void
    {
        $config = AuthConfig::fromArray($config);
        $pdo = new PdoResolver($config->db());

        self::$manager = new AuthManager(
            config: $config,
            pdo: $pdo,
            session: new SessionManager(),
        );

        self::$manager->boot();
    }

    public static function loadConfig(string $path): void
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException("Auth config file not found: {$path}");
        }

        $config = require $path;

        if (!is_array($config)) {
            throw new \RuntimeException("Auth config file must return an array.");
        }

        self::configure($config);
    }

    public static function attempt(string $login, string $password): AuthUser|false
    {
        return self::manager()->attempt($login, $password);
    }

    public static function login(array $user): AuthUser
    {
        return self::manager()->login($user);
    }

    public static function logout(): void
    {
        self::manager()->logout();
    }

    public static function check(): bool
    {
        return self::manager()->check();
    }

    public static function user(): ?AuthUser
    {
        return self::manager()->user();
    }

    public static function id(): int|string|null
    {
        return self::user()?->id();
    }

    public static function hasRole(string $role, string ...$roles): bool
    {
        return self::manager()->hasRole($role, ...$roles);
    }

    public static function rememberUser(): void
    {
        self::manager()->rememberUser();
    }

    public static function error(): ?string
    {
        return self::manager()->error();
    }

    private static function manager(): AuthManager
    {
        if (!self::$manager) {
            throw new \RuntimeException('Auth is not configured. Call Auth::configure() first.');
        }

        return self::$manager;
    }
}
