<?php

use Eril\Auth\Auth;

include __DIR__ . "/../vendor/autoload.php";
include __DIR__ . "/boot.php";



if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $auth = Auth::attempt($_POST['user'], $_POST['pass']);

    if ($auth) {
        $msg = "Login Sucesso - welcome " . $auth->name();
    } else {
        $msg = "Login Falhou";
    }

    
}


?>

<h2><?= $msg ?? Auth::user()?->name ?></h2>

<h1>Login</h1>


<form action="" method="post">
    <input type="text" name="user" placeholder="username">
    <input type="password" name="pass" id="" placeholder="Password">
    <button type="submit">Entrar</button>
</form>