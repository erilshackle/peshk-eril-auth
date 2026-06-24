<?php

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
return [

    'admin' => [
        '*',
    ],

    'mod' => [
        'appointments.*',
        'patients.view',
        'activities.*',
    ],

    'user' => [
        'appointments.view',
        'appointments.create',
        'appointments.cancel',

        'profile.view',
        'profile.update',
    ],
];
