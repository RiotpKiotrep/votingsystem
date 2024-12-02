<?php
session_start();

if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true)
{
    header("Location: admin_auth.php");
    die;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin panel</title>
</head>
<body>
    <h1>Admin panel</h1>
    <p>Options</p>
    <div class = "menu">
        <form action="add_voting.php" method="get">
            <button type="submit">Add voting</button>
        </form>
        <form action="manage_votings.php" method="get">
            <button type="submit">End or remove voting</button>
        </form>
        <form action="count.php" method="get">
            <button type="submit">Count votes</button>
        </form>
    </div>
</body>
</html>