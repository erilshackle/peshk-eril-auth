<?php

require __DIR__ . '/bootstrap.php';

use Eril\Auth\Auth;

/*
|--------------------------------------------------------------------------
| OAuth validation happens here.
|--------------------------------------------------------------------------
|
| Validate the Google/Facebook/GitHub token using your preferred library.
| Once you have the provider identifier, call loginWithProvider().
|
*/

$provider = 'google';
$providerId = '123456789';

$user = Auth::loginWithProvider($provider, $providerId);

if (!$user) {
    exit('Provider login failed.');
}

echo "Welcome {$user->name()}!";