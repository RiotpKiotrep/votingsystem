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
    <title>End voting</title>
</head>
<body>
    <div class="header"><h1>End voting</h1></div>
    <div class="menu">
        <form action="voting_handler.php" method="post">
            <label for="voting_id">Choose voting to end:</label>
            <select id="voting_id" name="id" required>
                <?php foreach($votings as $voting): if(!$voting['voting_ended']):?>
                    <option value="<?php echo $voting['id'];?>">
                        <?php echo $voting['title'];?>
                    </option>
                <?php endif; endforeach;?>
            </select>
            <button type="submit" name="action" value="end">End voting</button>
        </form>
    
        <br>
        <button class="return" onclick="document.location.href='admin_panel.php'">Return to admin panel</button>
    </div>
</body>
</html>