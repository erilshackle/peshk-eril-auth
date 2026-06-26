<?php

require __DIR__ . '/bootstrap.php';

use Eril\Auth\Auth;

if (Auth::guest()) {
    exit('Authentication required.');
}

echo 'Hello ' . Auth::user()->name();