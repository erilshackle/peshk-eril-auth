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

    /*
    |--------------------------------------------------------------------------
    | Remember Me
    |--------------------------------------------------------------------------
    |
    | Requires two nullable columns in your users table:
    | remember_selector and remember_token.
    |
    */

    'remember_enabled' => false,
    'remember_cookie' => 'remember_token',
    'remember_selector_field' => 'remember_selector',
    'remember_token_field' => 'remember_token',
    'remember_lifetime' => 60 * 60 * 24 * 30,

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

];