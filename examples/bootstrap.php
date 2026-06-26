<?php

use Eril\Auth\Auth;

Auth::loadConfig(
    __DIR__ . '/config/auth.php',
    createIfMissing: true
);

Auth::configure([
    'db' => new PDO('sqlite:database.db'),
    'profiles' => [
        'client' => [
            'table' => "clients",
            "foreign_key" => "user_id" 
        ]
    ],
    'rate_limit' => [
        'key' => 'login_ip'
    ],
    "login_field" => 'name',
    'remember_lifetime' => 1
]);
