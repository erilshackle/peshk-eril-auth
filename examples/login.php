<?php

require __DIR__ . '/bootstrap.php';

use Eril\Auth\Auth;

$login = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';

$user = Auth::attempt($login, $password);

if (!$user) {
    exit(Auth::error());
}

if (!empty($_POST['remember'])) {
    Auth::rememberUser();
}

echo "Welcome {$user->name()}!";