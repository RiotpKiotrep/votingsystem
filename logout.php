<?php

session_start();

require_once 'functions.php';

if(isset($_SESSION['user_id'])) {
    $pdo = getDB('login_system_db');
    $stmt = $pdo->prepare("SELECT email FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($user) {
        logger("User " . $user['email'] . " logged out");
    }

    session_unset();
    session_destroy();
}

header("Location: login.php");
die;