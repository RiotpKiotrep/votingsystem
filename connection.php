<?php

$host = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbName = "login_system_db";

$conn = new mysqli($host, $dbUsername, $dbPassword, $dbName);
if(mysqli_connect_error())
{
    die('Connect error('. mysqli_connect_errno().')'. mysqli_connect_error());
}