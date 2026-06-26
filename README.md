# Eril Auth

Lightweight authentication and authorization library for modern PHP applications.

Eril Auth provides session-based authentication, persistent login (Remember Me), role-based authorization (RBAC), login rate limiting, provider login, user profiles and diagnostics without requiring a framework.

---

## Features

* Session-based authentication
* Multiple login fields (email, username, phone, etc.)
* Persistent login (Remember Me)
* Login rate limiting
* Login with external providers
* Role-based authorization (RBAC)
* Wildcard permissions
* User profiles
* Authentication diagnostics
* Configuration publisher
* CLI installer
* Framework agnostic
* PHP 8.1+

---

## Requirements

* PHP 8.1+
* PDO Extension

---

# Installation

Install via Composer:

```bash
composer require eril/peshk-auth
```

Generate the default configuration:

```bash
vendor/bin/auth install
```

Or publish only the configuration files:

```bash
vendor/bin/auth publish
```

Verify your installation:

```bash
vendor/bin/auth diagnose
```

---

# Quick Start

Load the configuration during application bootstrap.

```php
use Eril\Auth\Auth;

Auth::loadConfig(
    __DIR__ . '/config/auth.php',
    createIfMissing: true
);
```

This automatically creates:

```text
config/
├── auth.php
└── permissions.php
```

Authenticate a user:

```php
$user = Auth::attempt(
    $_POST['login'],
    $_POST['password']
);

if (!$user) {
    die(Auth::error());
}

echo "Welcome {$user->name()}";
```

---

# Configuration

The installer generates a complete `config/auth.php` file with documentation for every available option.

A minimal configuration looks like this:

```php
<?php

use PDO;

return [

    'db' => fn (): PDO => new PDO(
        'mysql:host=localhost;dbname=app;charset=utf8mb4',
        'root',
        ''
    ),

    'login_field' => [
        'email',
        'username',
    ],

    'remember_enabled' => true,

    'permissions' => require __DIR__.'/permissions.php',

];
```

The generated configuration file contains additional options for:

* Session handling
* User table mapping
* Rate limiting
* Remember Me
* Profiles
* Provider login

---

# Authentication

## Login

Authenticate using the configured login field(s).

```php
$user = Auth::attempt(
    $login,
    $password
);

if (!$user) {
    echo Auth::error();
    return;
}
```

If multiple login fields are configured:

```php
'login_field' => [
    'email',
    'username',
    'phone',
],
```

The following will all work:

```php
Auth::attempt('john@example.com', 'secret');

Auth::attempt('john', 'secret');

Auth::attempt('+351999999999', 'secret');
```

---

## Manual Login

You may authenticate a user manually.

```php
Auth::login([

    'id' => 1,

    'name' => 'Administrator',

    'email' => 'admin@example.com',

    'role' => 'admin',

]);
```

This is useful when authentication is handled by another system.

---

## Login with Provider

Eril Auth supports authentication through externally validated identity providers.

Typical flow:

```
Google OAuth
        │
        ▼
Application validates token
        │
        ▼
Auth::loginWithProvider()
```

Example:

```php
$user = Auth::loginWithProvider(
    'google',
    $googleUserId
);
```

> Eril Auth does **not** implement OAuth.
>
> Token validation must be performed by your application or an OAuth library before calling `loginWithProvider()`.

---

## Remember Me

Enable persistent login:

```php
'remember_enabled' => true,
```

After a successful login:

```php
if (!empty($_POST['remember'])) {
    Auth::rememberUser();
}
```

On future visits, Eril Auth automatically restores the session if the remember cookie is valid.

### Database

Remember Me requires two nullable columns in the users table.

```sql
ALTER TABLE users

ADD remember_selector VARCHAR(64) NULL,

ADD remember_token VARCHAR(255) NULL;
```

---

## Login Rate Limiting

Protect login attempts against brute-force attacks.

```php
'rate_limit' => [

    'enabled' => true,

    'max_attempts' => 5,

    'decay_seconds' => 300,

    'key' => 'login_ip',

],
```

Available key strategies:

| Strategy | Description          |
| -------- | -------------------- |
| login    | Limit by login value |
| ip       | Limit by client IP   |
| login_ip | Limit by login + IP  |

---

## Logout

```php
Auth::logout();
```

---

## Authentication State

Determine whether a user is authenticated.

```php
if (Auth::check()) {

}
```

Or check if no user is authenticated.

```php
if (Auth::guest()) {

}
```

---

## Current User

Retrieve the authenticated user.

```php
$user = Auth::user();
```

Retrieve the authenticated user's identifier.

```php
$id = Auth::id();
```

Retrieve the authenticated user's profile.

```php
$profile = Auth::profile();
```

# AuthUser

`AuthUser` represents the currently authenticated user.

It provides convenient methods for accessing user data, checking roles and permissions, and loading the associated profile.

---

## Standard Methods

```php
$user = Auth::user();

$user->id();

$user->name();

$user->login();

$user->role();
```

---

## Role Checks

```php
$user->is('admin');

$user->hasRole(
    'admin',
    'manager'
);
```

Equivalent using the facade:

```php
Auth::is('admin');

Auth::hasRole(
    'admin',
    'manager'
);
```

---

## Permission Checks

```php
$user->can('appointments.create');

$user->cannot('appointments.delete');
```

Equivalent:

```php
Auth::can('appointments.create');

Auth::cannot('appointments.delete');
```

---

## Dynamic Properties

Every column from the original database row remains available.

```php
echo $user->email;

echo $user->phone;

echo $user->created_at;
```

---

## Array Access

`AuthUser` implements `ArrayAccess`.

```php
echo $user['email'];

echo $user['phone'];
```

---

## Utility Methods

Convert the authenticated user to an array.

```php
$array = $user->toArray();
```

Select only specific fields.

```php
$user->only(
    'id',
    'name',
    'email'
);
```

Exclude fields.

```php
$user->except(
    'raw'
);
```

Access the original database row.

```php
$row = $user->raw();
```

---

# Profiles

Many applications store additional information in separate profile tables.

Example:

```text
users
├── id
├── name
├── email
├── password
└── role

patients
├── id
├── user_id
├── phone
├── birth_date
└── address

professionals
├── id
├── user_id
├── bio
├── experience
└── license
```

Configure the relationship:

```php
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
```

Retrieve the authenticated profile:

```php
$profile = Auth::profile();
```

or

```php
$profile = Auth::user()?->profile();
```

Access profile data:

```php
echo $profile->bio;

echo $profile['bio'];

echo $profile->get('bio', default: 'no bio');
```

Profiles are loaded lazily and remain independent from `AuthUser`.

This avoids unnecessary database queries and keeps authentication data separate from domain-specific information.

---

# Authorization (RBAC)

Permissions are configured per role.

Example:

```php
return [

    'admin' => [

        '*',

    ],

    'professional' => [

        'appointments.*',

        'patients.view',

        'activities.*',

    ],

    'patient' => [

        'appointments.view',

        'appointments.create',

        'appointments.cancel',

        'profile.view',

        'profile.update',

    ],

];
```

basically:
```php
// structure example
'role' => [
    'resource.action',
    ...
]
```

---

## Checking Permissions

```php
if (Auth::can('appointments.create')) {

}
```

Negated check:

```php
if (Auth::cannot('appointments.delete')) {

}
```

Require a permission:

```php
Auth::authorize(
    'appointments.create'
);
```

If authorization fails, an `AuthorizationException` is thrown.

---

## Wildcards

Grant every permission:

```php
'admin' => [

    '*',

],
```

Grant every permission within a namespace:

```php
'professional' => [

    'appointments.*',

],
```

Which automatically matches:

```text
appointments.view
appointments.create
appointments.update
appointments.delete
appointments.cancel
...
```

---

# Diagnostics

Inspect the current authentication configuration.

```php
$result = Auth::diagnose();
```

Typical output:

```php
[
    'pdo_connection' => [
        'ok' => true,
        'message' => 'PDO connection is working.',
    ],

    'user_table' => [
        'ok' => true,
        'message' => 'Table [users] exists.',
    ],

    'login_field_email' => [
        'ok' => true,
        'message' => 'Column [email] exists.',
    ],

    'provider_table' => [
        'ok' => true,
        'message' => 'Table [user_providers] exists.',
    ],
]
```

The diagnostic tool validates:

* Database connection
* User table
* User columns
* Session configuration
* Remember Me columns
* Provider tables
* Profile mappings
* Permission configuration

---

# Exceptions

Eril Auth provides dedicated exceptions.

```php
Eril\Auth\Exceptions\AuthException

Eril\Auth\Exceptions\AuthenticationException

Eril\Auth\Exceptions\AuthorizationException

Eril\Auth\Exceptions\ConfigurationException
```

Example:

```php
try {

    Auth::authorize('admin.panel');

} catch (AuthorizationException $e) {

    echo $e->getMessage();

}
```

---

# API Reference

## Configuration

```php
Auth::configure()

Auth::loadConfig()
```

---

## Authentication

```php
Auth::attempt()

Auth::login()

Auth::loginWithProvider()

Auth::logout()

Auth::rememberUser()
```

---

## Current User

```php
Auth::check()

Auth::guest()

Auth::user()

Auth::profile()

Auth::id()
```

---

## Roles

```php
Auth::hasRole()

Auth::is()
```

---

## Permissions

```php
Auth::can()

Auth::cannot()

Auth::authorize()
```

---

## Utilities

```php
Auth::error()

Auth::diagnose()
```

---

# Out of Scope

Eril Auth intentionally focuses on session-based authentication and authorization.

The following features are intentionally **not** included:

* User registration
* Password reset
* Email verification
* OAuth flows
* JWT authentication
* WebAuthn / Passkeys
* Multi-factor authentication (MFA/TOTP)
* ORM integration

These features are better implemented by your application or by dedicated libraries.

---

# License

Licensed under the MIT License.
