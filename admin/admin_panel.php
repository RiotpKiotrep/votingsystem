<?php
session_start();
require_once '../functions.php';
check_admin_login();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css">
    <title>Admin panel</title>
</head>
<body>
    <div class="header"><h1>Admin panel</h1></div>
    <div class="intro"><p>Options</p></div>
    <div class = "menu">
        <form action="add_voting.php" method="get">
            <button type="submit">Add voting</button>
        </form>
        <form action="add_users.php" method="get">
            <button type="submit">Add users</button>
        </form>
        <form action="manage_votings.php" method="get">
            <button type="submit">End voting</button>
        </form>
        <form action="count.php" method="get">
            <button type="submit">Count votes</button>
        </form>
        <br>
        <form action="logout.php" method="get">
            <button class="return" type="submit">Logout</button>
        </form>
    </div>
</body>
</html>