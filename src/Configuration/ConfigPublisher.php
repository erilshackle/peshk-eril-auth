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
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | Provide a PDO instance or a callable returning a PDO instance.
    |
    | Example:
    |
    | 'db' => $pdo,
    |
    | or:
    |
    | 'db' => fn (): PDO => new PDO(
    |     'mysql:host=localhost;dbname=app;charset=utf8mb4',
    |     'root',
    |     ''
    | ),
    |
    */

    'db' => fn (): PDO => new PDO(
        'mysql:host=localhost;dbname=app;charset=utf8mb4',
        'root',
        ''
    ),

    /*
    |--------------------------------------------------------------------------
    | User Table
    |--------------------------------------------------------------------------
    */

    'user_table' => 'users',

    /*
    |--------------------------------------------------------------------------
    | User Fields
    |--------------------------------------------------------------------------
    |
    | These columns are used to identify, authenticate and normalize the
    | authenticated user object.
    |
    */

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
    | session_name is the $_SESSION key used by the auth library.
    | session_lifetime is expressed in seconds.
    |
    */

    'session_name' => 'auth_user',
    'session_lifetime' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Remember Me
    |--------------------------------------------------------------------------
    |
    | Requires these nullable columns in your users table:
    |
    | remember_selector VARCHAR(64) NULL
    | remember_token VARCHAR(255) NULL
    |
    */

    'remember_enabled' => true,
    'remember_cookie' => 'remember_token',
    'remember_selector_field' => 'remember_selector',
    'remember_token_field' => 'remember_token',
    'remember_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Profiles
    |--------------------------------------------------------------------------
    |
    | Optional profile table mapping by role.
    |
    | Example:
    |
    | role "patient" loads from table "patients"
    | where patients.user_id = authenticated user id.
    |
    */

    'profiles' => [

        'patient' => [
            'table' => 'patients',
            'foreign_key' => 'user_id',
        ],

        'professional' => [
            'table' => 'professionals',
            'foreign_key' => 'user_id',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Login
    |--------------------------------------------------------------------------
    |
    | Settings for login with provider .
    |
    */

    'providers' => [

        'table' => 'user_providers',

        'provider_field' => 'provider',

        'provider_id_field' => 'provider_id',

        'user_id_field' => 'user_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    |
    | Role-based permissions are stored in a separate file by default.
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
