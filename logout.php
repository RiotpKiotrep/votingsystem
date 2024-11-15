<?php

session_start();

include("connection.php");
include("functions.php");

$user_data = check_login($conn);
$email = $user_data['email'];
$log = "User $email logged out";
logger($log);

if(isset($_SESSION['user_id']))
{
    unset($_SESSION['user_id']);
}

header("Location: login.php");
die;