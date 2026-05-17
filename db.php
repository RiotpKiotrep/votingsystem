<?php

function getDB($dbName = 'voting_system_db') {
    $host = "localhost";
    $user = "root";
    $pass = "";
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}