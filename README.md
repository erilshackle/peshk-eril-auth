# Eril Auth

Simple authentication and authorization library for PHP.

Eril Auth provides session-based authentication, persistent login (remember-me), role-based authorization (RBAC), permission wildcards and diagnostics with minimal configuration and no framework dependency.

## Features

* Session-based authentication
* Persistent login (Remember Me)
* Role-based authorization (RBAC)
* Wildcard permissions
* User role checks
* Authentication diagnostics
* PDO support
* Automatic configuration file generation
* Framework agnostic
* PHP 8.1+

---

## Installation

Install via Composer:

```bash
composer require eril/auth
```

---

## Quick Start

Create or load the configuration file:

```php
use Eril\Auth\Auth;

Auth::loadConfig(
    __DIR__ . '/config/auth.php',
    createIfMissing: true
);
```

This will automatically create:

```txt
config/
├── auth.php
└── permissions.php
```

if they do not exist.

---

## Configuration

Example `config/auth.php`:

```php
<?php

return [

    'db' => fn () => new PDO(
        'mysql:host=localhost;dbname=app;charset=utf8mb4',
        'root',
        ''
    ),

    'user_table' => 'users',

    'id_field' => 'id',
    'name_field' => 'name',
    'login_field' => 'email',
    'password_field' => 'password',
    'role_field' => 'role',

    'session_name' => 'auth_user',
    'session_lifetime' => 3600,

    'remember_enabled' => true,
    'remember_cookie' => 'remember_token',
    'remember_selector_field' => 'remember_selector',
    'remember_token_field' => 'remember_token',
    'remember_days' => 30,

    'permissions' => require __DIR__ . '/permissions.php',
];
```

---

## Authentication

### Login

```php
$user = Auth::attempt(
    $_POST['email'],
    $_POST['password']
);

if (!$user) {
    echo Auth::error();
    return;
}
```

### Login with Remember Me

```php
$user = Auth::attempt(
    $_POST['email'],
    $_POST['password']
);

if ($user && !empty($_POST['remember'])) {
    Auth::rememberUser();
}
```

### Manual Login

```php
Auth::login([
    'id' => 1,
    'name' => 'Administrator',
    'email' => 'admin@example.com',
    'role' => 'admin',
]);
```

### Check Authentication

```php
if (Auth::check()) {
    //
}
```

### Current User

```php
$user = Auth::user();
```

### Current User ID

```php
$id = Auth::id();
```

### Logout

```php
Auth::logout();
```

---

## Roles

### Check Single Role

```php
if (Auth::hasRole('admin')) {
    //
}
```

### Check Multiple Roles

```php
if (Auth::hasRole('admin', 'manager')) {
    //
}
```

### Using AuthUser

```php
if (Auth::user()?->is('admin')) {
    //
}
```

---

## Authorization (RBAC)

Permissions are configured by role.

Example `config/permissions.php`:

```php
<?php

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

### Check Permission

```php
if (Auth::can('appointments.create')) {
    //
}
```

### Negated Check

```php
if (Auth::cannot('appointments.delete')) {
    //
}
```

### Authorize

Throws an `AuthorizationException` when the user is not authorized.

```php
Auth::authorize('appointments.create');
```

### Using AuthUser

```php
$user = Auth::user();

if ($user?->can('appointments.create')) {
    //
}
```

---

## Wildcard Permissions

Grant all permissions:

```php
'admin' => [
    '*',
];
```

Grant all permissions within a group:

```php
'professional' => [
    'appointments.*',
];
```

Allows:

```php
appointments.view
appointments.create
appointments.update
appointments.delete
```

---

## Remember Me

To use persistent login, add the following nullable columns to your users table:

```sql
ALTER TABLE users
ADD remember_selector VARCHAR(64) NULL,
ADD remember_token VARCHAR(255) NULL;
```

Enable remember-me in configuration:

```php
'remember_enabled' => true,
```

Remember the authenticated user:

```php
Auth::rememberUser();
```

The library automatically restores the session when a valid remember-me cookie is present.

---

## AuthUser

### Standard Methods

```php
$user->id();
$user->name();
$user->login();
$user->role();
```

### Role Checks

```php
$user->is('admin');

$user->hasRole(
    'admin',
    'manager'
);
```

### Permission Checks

```php
$user->can('appointments.create');

$user->cannot('appointments.delete');
```

### Dynamic Properties

Any field from the original database row is available:

```php
$user->email;

$user->phone;

$user->created_at;
```

### Array access:

```php
$user['email'];
$user['phone'];
```

### Convert to Array

```php
$user->toArray();
```

### Select Fields

```php
$user->only(
    'id',
    'name'
);
```

### Exclude Fields

```php
$user->except(
    'raw'
);
```



---

## Profiles

Some applications store role-specific data in separate profile tables.

Example:

```txt
users
├── id
├── name
├── email
├── password
└── role

patients
├── id
├── user_id
├── birth_date
└── phone

professionals
├── id
├── user_id
├── bio
└── experience
```

Configure profiles by role:

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

Load the authenticated user's profile:

```php
$profile = Auth::profile();
```

Or through the authenticated user:

```php
$profile = Auth::user()?->profile();
```

Access profile data:

```php
echo $profile?->bio;

echo $profile?['bio'];

echo $profile?->get('bio');
```

Profiles are loaded on demand. They are not automatically merged into `AuthUser`.

---

## Diagnostics

Inspect the current authentication configuration:

```php
print_r(
    Auth::diagnose()
);
```

Example output:

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
]
```

---

## Exceptions

The library provides dedicated exceptions:

```php
Eril\Auth\Exceptions\AuthException

Eril\Auth\Exceptions\ConfigurationException

Eril\Auth\Exceptions\AuthenticationException

Eril\Auth\Exceptions\AuthorizationException
```

Example:

```php
try {
    Auth::authorize('admin.access');
} catch (AuthorizationException $e) {
    //
}
```

---

## API Reference

### Authentication

```php
Auth::configure()

Auth::loadConfig()

Auth::attempt()

Auth::login()

Auth::logout()

Auth::check()

Auth::user()

Auth::id()

Auth::error()
```

### Authorization

```php
Auth::hasRole()

Auth::can()

Auth::cannot()

Auth::authorize()
```

### Remember Me

```php
Auth::rememberUser()
```

### Diagnostics

```php
Auth::diagnose()
```

---

## Requirements

* PHP 8.1+
* PDO Extension

---

## License

MIT License.
