<?php
session_start();

if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true)
{
    header("Location: admin_auth.php");
    die;
}

$votings_file = file_get_contents('../votings.json');
$votings = json_decode($votings_file, true);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add voting</title>
</head>
<body>
    <h1>Add voting</h1>
    <form action="voting_handler.php" method="post">
        <label for="voting_name">Voting name (can contain only letters, numbers and underscores):</label>
        <input type="text" id="voting_name" name="voting_name" required><br>

        <label for="title">Title (visible name):</label>
        <input type="text" id="title" name="title" required><br>

        <label for="description">Description:</label>
        <input type="text" id="description" name="description" required><br>

        <label for="candidates">Candidates (separate with comma):</label>
        <input type="text" id="candidates" name="candidates" required><br>

        <button type="submit" name="action" value="add">Add voting</button>
    </form>
    <br>

    <input type="button" value="Return to admin panel" onclick="document.location.href='admin_panel.php'" />
</body>
</html>