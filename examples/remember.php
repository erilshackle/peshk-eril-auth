<?php

require __DIR__ . '/bootstrap.php';

use Eril\Auth\Auth;


// Esse exemplo mostra que o Remember Me funciona automaticamente.


if (Auth::check()) {
    echo 'Authenticated as: ' . Auth::user()->name();
} else {
    echo 'Not authenticated.';
}