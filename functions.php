<?php

require_once 'db.php';

function check_login()
{
    $conn = getDB('login_system_db');
    if(isset($_SESSION['user_id']))
    {
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if($user_data) return $user_data;
    }
    // redirect to login page
    $log = "User has been redirected to login page";
    logger($log);

    header("location: login.php");
    die;
}

function random_num($length)
{
    $text = "";
    if($length < 5)
    {
        $length < 5;
    }
    $len = rand(4, $length);

    for($i=0; $i < $len; $i++)
    {
        $text .= rand(0,9);
    }
    return $text;
}

function getVotingConfig($votingName) {
    $votings = json_decode(file_get_contents('votings.json'), true);
    foreach ($votings as $v) {
        if ($v['voting_name'] === $votingName) return $v;
    }
    return null;
}

function getVotingConfigById($votingId) {
    $votings = json_decode(file_get_contents('votings.json'), true);
    foreach ($votings as $v) {
        if ($v['id'] === $votingId) return $v;
    }
    return null;
}

function logger($log) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $time = date('m/d/y h:iA');
    file_put_contents('log.log', "$ip\t$time\t$log\r\n", FILE_APPEND);
}

function generate_token()
{
    return bin2hex(random_bytes(32));
}

function validate_password($password)
{
    $min_length = 8;
    $uppercase = preg_match('/[A-Z]/', $password);
    $lowercase = preg_match('/[a-z]/', $password);
    $digit = preg_match('/\d/', $password);
    $special = preg_match('/[\W_]/', $password);

    return strlen($password) >= $min_length && $uppercase && $lowercase && $digit && $special;
}

use phpseclib3\Crypt\RSA;

function GetRSAKeys()
{
    $private = RSA::loadPrivateKey(file_get_contents('private.pem'));
    $public = RSA::loadPublicKey(file_get_contents('public.pem'));
    return [$private, $public];
}

function GetPublicKey()
{
    $public = RSA::loadPublicKey(file_get_contents('public.pem'));
    return $public;
}