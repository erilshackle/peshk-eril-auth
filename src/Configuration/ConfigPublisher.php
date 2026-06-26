<?php

namespace Eril\Auth\Configuration;

use Eril\Auth\Exceptions\ConfigurationException;

final class ConfigPublisher
{
    /**
     * Publish the default auth configuration files.
     *
     * This method creates:
     *
     * - auth.php at the given path
     * - permissions.php in the same directory
     *
     * Existing files are not overwritten.
     *
     * @throws ConfigurationException
     */
    public static function publish(string $authConfigPath): void
    {
        $directory = dirname($authConfigPath);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new ConfigurationException("Unable to create config directory: {$directory}");
        }

        if (!is_file($authConfigPath)) {
            self::write($authConfigPath, self::authTemplate());
        }

        $permissionsPath = $directory . DIRECTORY_SEPARATOR . 'permissions.php';

        if (!is_file($permissionsPath)) {
            self::write($permissionsPath, self::permissionsTemplate());
        }
    }

    /**
     * Write a file safely.
     *
     * @throws ConfigurationException
     */
    private static function write(string $path, string $contents): void
    {
        if (file_put_contents($path, $contents) === false) {
            throw new ConfigurationException("Unable to write config file: {$path}");
        }
    }

    /**
     * Default auth.php template.
     */
    private static function authTemplate(): string
    {
        return <<<'PHP'
<?php

use PDO;

return [

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    |
    | Configure the database connection used by the authentication system.
    | Provide either a PDO instance or a callable returning a PDO instance.
    |
    */

    'db' => fn (): PDO => new PDO(
        'mysql:host=localhost;dbname=app;charset=utf8mb4',
        'root',
        ''
    ),

    /*
    |--------------------------------------------------------------------------
    | Users Table
    |--------------------------------------------------------------------------
    |
    | Define where users are stored and which columns are used for
    | authentication, identity and authorization.
    |
    | login_field may be a string or an array:
    |
    | 'login_field' => 'email',
    | 'login_field' => ['email', 'username', 'phone'],
    |
    */

    'user_table' => 'users',

    'id_field' => 'id',
    'name_field' => 'name',
    'login_field' => 'email',
    'password_field' => 'password',
    'role_field' => 'role',

    /*
    |--------------------------------------------------------------------------
    | Session
    |--------------------------------------------------------------------------
    |
    | Configure how authenticated users are stored in the session.
    | Set session_lifetime to null to use PHP's default session settings.
    |
    */

    'session_name' => 'auth_user',
    'session_lifetime' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Configure additional security features such as login rate limiting
    | and "Remember Me" persistent authentication.
    |
    */

    'rate_limit' => [
        'enabled' => true,
        'max_attempts' => 5,
        'decay_seconds' => 300,
        'key' => 'login_ip',
    ],

    'remember_enabled' => false,
    'remember_cookie' => 'remember_token',
    'remember_selector_field' => 'remember_selector',
    'remember_token_field' => 'remember_token',
    'remember_lifetime' => 60 * 60 * 24 * 30,

    /*
    |--------------------------------------------------------------------------
    | Profiles
    |--------------------------------------------------------------------------
    |
    | Optionally map user roles to profile tables.
    | Profiles are loaded on demand and remain separate from the user record.
    |
    */

    'profiles' => [
        // 'patient' => [
        //     'table' => 'patients',
        //     'foreign_key' => 'user_id',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Login
    |--------------------------------------------------------------------------
    |
    | Configure login using externally validated identity providers
    | such as Google, Facebook or GitHub.
    |
    */

    'providers' => [
        // 'table' => 'user_providers',
        // 'provider_field' => 'provider',
        // 'provider_id_field' => 'provider_id',
        // 'user_id_field' => 'user_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    |
    | Configure role-based permissions.
    | By default, permissions are loaded from a separate configuration file.
    |
    */

    'permissions' => require __DIR__ . '/permissions.php',

];
PHP;
    }

    /**
     * Default permissions.php template.
     */
    private static function permissionsTemplate(): string
    {
        return <<<'PHP'
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Role-Based Permissions
    |--------------------------------------------------------------------------
    |
    | Define permissions by role.
    |
    | Exact permission:
    |   'appointments.create'
    |
    | Wildcard permission:
    |   'appointments.*'
    |
    | Full access:
    |   '*'
    |
    */

    'admin' => [
        '*',
    ],

    'user' => [
        'profile.view',
        'profile.update',
    ],
];

PHP;
    }
}
