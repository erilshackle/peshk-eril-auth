<?php

require __DIR__ . '/bootstrap.php';

use Eril\Auth\Auth;

if (Auth::guest()) {
    exit;
}

$profile = Auth::profile();

echo '<pre>';

print_r($profile?->toArray());