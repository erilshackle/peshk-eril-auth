<?php

require __DIR__ . '/bootstrap.php';

use Eril\Auth\Auth;

if (Auth::cannot('appointments.create')) {
    exit('Permission denied.');
}

echo 'Permission granted.';