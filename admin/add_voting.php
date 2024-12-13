<?php
session_start();

if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true)
{
    header("Location: admin_auth.php");
    die;
}

$inactive_time_limit = 5*60; // 1 * 60 seconds
if(isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $inactive_time_limit)
{
    session_unset();
    session_destroy();
    header("Location: admin_auth.php?m=session_expired");
    die;
}
$_SESSION['last_activity'] = time();

$votings_file = file_get_contents('../votings.json');
$votings = json_decode($votings_file, true);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css">
    <title>Add voting</title>
</head>
<body>
    <div class="header"><h1>Add voting</h1></div>
    <div class="menu">
        <form action="voting_handler.php" method="post">
            <label for="voting_name">Voting name (can contain only letters, numbers and underscores):</label>
            <input type="text" id="voting_name" name="voting_name" required>

            <label for="title">Title (visible name):</label>
            <input type="text" id="title" name="title" required>

            <label for="description">Description:</label>
            <input type="text" id="description" name="description" required>

            <label for="candidates">Candidates (separate with comma):</label>
            <input type="text" id="candidates" name="candidates" required>

            <button type="submit" name="action" value="add">Add voting</button>
        </form>

        <br>
        <button class="return" onclick="document.location.href='admin_panel.php'">Return to admin panel</button>
    </div>

    
</body>
</html>