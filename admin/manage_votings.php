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
    <title>Manage votings</title>
</head>
<body>
    <h1>Manage votings</h1>
    <form action="voting_handler.php" method="post">
        <label for="voting_id">Choose voting to end or delete:</label>
        <select id="voting_id" name="id" required>
            <?php foreach($votings as $voting):?>
                <option value="<?php echo $voting['id'];?>">
                    <?php echo $voting['title'];?>
                </option>
            <?php endforeach;?>
        </select><br><br>
        <button type="submit" name="action" value="delete">End or delete voting</button>
    </form>
</body>
</html>