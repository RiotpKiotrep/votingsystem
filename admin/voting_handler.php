<?php
session_start();

require_once '../functions.php';

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

if($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $votings_file = file_get_contents('../votings.json');
    $votings = json_decode($votings_file, true);

    $action = $_POST['action'];

    $pdo = getDB('voting_system_db');

    if($action === 'add')
    {
        $voting_name = $_POST['voting_name'];
        if(!preg_match('/^[a-zA-Z0-9_]+$/', $voting_name))
        {
            die("Wrong voting name");
        }

        foreach($votings as $voting)
        {
            if($voting['voting_name'] === $voting_name)
            {
                die("This voting name is unavailable");
            }
        }

        $sql = "CREATE TABLE `$voting_name` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(256) NOT NULL,
            candidate VARCHAR(256) NOT NULL,
            token VARCHAR(256) NOT NULL
        )";
        
        try
        {
            $pdo->exec($sql);
            $new_voting = [
                'id' => count($votings)+1,
                'voting_name' => $_POST['voting_name'],
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'candidates' => array_map('trim', explode(',', $_POST['candidates'])),
                'expiry_date' => $_POST['expiry_date'],
                'voting_ended' => false
            ];
            $votings[] = $new_voting;

            file_put_contents('../votings.json', json_encode($votings, JSON_PRETTY_PRINT));
            logger("Voting $voting_name added");
            echo "Voting added successfully.";
        }
        catch (PDOException $e)
        {
            "Error creating table: ".$e->getMessage();
        }
    }
    elseif($action === 'end')
    {
        $voting_id = (int)$_POST['id'];
        foreach($votings as &$voting)
        {
            if($voting['id'] === $voting_id)
            {
                $voting['voting_ended'] = true;
                logger("Voting " . $voting['voting_name'] . " ended");
                break;
            }
        }
        file_put_contents('../votings.json', json_encode($votings, JSON_PRETTY_PRINT));
        echo "Operation successful.";
    }
}


?>

<input type="button" value="Return to admin panel" onclick="document.location.href='admin_panel.php'" />