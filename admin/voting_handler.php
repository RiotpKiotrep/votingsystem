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

if($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $votings_file = file_get_contents('../votings.json');
    $votings = json_decode($votings_file, true);

    $action = $_POST['action'];
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

        $host = "localhost";
        $dbUsername = "root";
        $dbPassword = "";
        $dbName = "voting_system_db";
    
        $conn = new mysqli($host, $dbUsername, $dbPassword, $dbName);
        if(mysqli_connect_error())
        {
            die('Connect error('. mysqli_connect_errno().')'. mysqli_connect_error());
        }
        else
        {
            $sql = "SHOW TABLES LIKE '".mysqli_real_escape_string($conn, $voting_name)."'";
            $result = mysqli_query($conn, $sql);
            if($result && mysqli_num_rows($result) > 0)
            {
                die("This voting name is unavailable");
            }

            $new_voting = [
                'id' => count($votings)+1,
                'voting_name' => $_POST['voting_name'],
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'candidates' => array_map('trim', explode(',', $_POST['candidates'])),
                'voting_ended' => false
            ];
            $votings[] = $new_voting;

            $sql = "CREATE TABLE `$voting_name` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(256) NOT NULL,
                candidate VARCHAR(256) NOT NULL,
                token VARCHAR(256) NOT NULL
            )";
            if($conn->query($sql) === true)
            {
                "Voting added successfully";
            }
            else
            {
                "Error creating table: ".$conn->error;
            }
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
                break;
            }
        }
    }

    file_put_contents('../votings.json', json_encode($votings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "Operation successful<br><br>";
}


?>

<input type="button" value="Return to admin panel" onclick="document.location.href='admin_panel.php'" />