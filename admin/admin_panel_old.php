<?php
session_start();
require_once '../functions.php';
check_admin_login();

$votings_file = file_get_contents('../votings.json');
$votings = json_decode($votings_file, true);
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
        <?php if ($_SESSION['admin_role'] === 'SUPERADMIN'): ?>
            <form action="admin_management.php" method="get">
                <button type="submit">Manage administrators</button>
            </form>
        <?php endif; ?>
        <?php if ($_SESSION['admin_role'] === 'SUPERADMIN' || $_SESSION['admin_role'] === 'VOTING_ADMIN'): ?>
            <form action="add_voting.php" method="get">
                <button type="submit">Create voting</button>
            </form>
            <form action="voting_management.php" method="get">
                <label for="voting_id">Choose voting:</label>
                <select id="voting_id" name="id" required>
                    <option value="">-- Select voting --</option>
                    <?php foreach($votings as $voting):?>
                        <option value="<?php echo $voting['id'];?>">
                            <?php echo $voting['title'];?>
                        </option>
                    <?php endforeach;?>
                </select>
                <button type="submit">Manage voting</button>
            </form>
        <?php endif; ?>
        <br>
        <form action="logout.php" method="get">
            <button class="return" type="submit">Logout</button>
        </form>
    </div>
</body>
</html>