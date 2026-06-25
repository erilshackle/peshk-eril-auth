<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | A PDO instance or a callable returning a PDO instance.
    | 
    */

    'db' => fn(): PDO => new PDO("sqlite::memory:"),

    /*
    |--------------------------------------------------------------------------
    | User Table
    |--------------------------------------------------------------------------
    |
    | Database table containing authenticated users.
    |
    */

    'user_table' => 'users',

    /*
    |--------------------------------------------------------------------------
    | User Fields
    |--------------------------------------------------------------------------
    |
    | Column names used by the authentication system.
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
    | Session key used to store the authenticated user and
    | session lifetime in seconds.
    |
    */

    'session_name' => 'auth_user',

    'session_lifetime' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Remember Me
    |--------------------------------------------------------------------------
    |
    | Persistent login configuration.
    |
    */

    'remember_enabled' => true,

    'remember_cookie' => 'remember_token',

    'remember_selector_field' => 'remember_selector',

    'remember_token_field' => 'remember_token',

    'remember_days' => 30,


    /*
    |--------------------------------------------------------------------------
    | profiles 
    |--------------------------------------------------------------------------
    |
    | role => ['table', 'foreign_key']
    |
    */
    'profiles' => [

        'patient' => [
            'table' => 'pacientes',
            'foreign_key' => 'user_id',
        ],

        'professional' => [
            'table' => 'profissionais',
            'foreign_key' => 'user_id',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | providers 
    |--------------------------------------------------------------------------
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
    | Permissions (RBAC)
    |--------------------------------------------------------------------------
    |
    | Role => Permissions
    |
    | '*' grants all permissions.
    |
    */
    'permissions' => include __DIR__ . '/permissions.php',

];
