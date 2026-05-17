<?php

require_once 'functions.php';

if(isset($_GET['t']))
{
    $pdo = getDB('login_system_db');
    $token = $_GET['t'];

    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user)
    {
        $stmt = $pdo->prepare("UPDATE users SET verified = 1 WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);

        $log = "User with ID".$user['user_id']." verified";
        logger($log);
        
        echo "Account verified";
    }
    else
    {
        echo "Wrong token";
    }
}

?>