<?php

require __DIR__ . '/bootstrap.php';

use Eril\Auth\Auth;

Auth::logout();

echo 'Logged out.';