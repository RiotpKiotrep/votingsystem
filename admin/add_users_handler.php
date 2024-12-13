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
if(json_last_error() !== JSON_ERROR_NONE)
{
    die('Error decoding JSON: '.json_last_error_msg());
}

if($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $emails = $_POST['emails'] ?? '';
    $voting_id = $_POST['id'] ?? '';
    if(empty($emails) || empty($voting_id))
    {
        die("No email addresses provided");
    }

    $votingdb = null;
    foreach($votings as $voting)
    {
        if($voting['id'] == $voting_id)
        {
            $votingdb = $voting['voting_name'];
            break;
        }
    }
    if(!$votingdb)
    {
        die("Voting name not found");
    }

    $emails = array_map('trim', explode(',', $emails));
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
        $sql = "INSERT INTO permitted_users (email, voting) VALUES (?, ?)";
        $stmt = mysqli_stmt_init($conn);
        if(!mysqli_stmt_prepare($stmt, $sql))
        {
            die("SQL preparation error: " . mysqli_error($conn));
        }
        foreach ($emails as $email)
        {
            $email = trim($email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            {
                echo "Invalid email format: $email. Skipping.<br>";
                continue;
            }

            $hashed_email = hash('sha256', $email);
            mysqli_stmt_bind_param($stmt, "ss", $hashed_email, $votingdb);
        
            if (!mysqli_stmt_execute($stmt))
            {
                echo "Failed to insert $email into voting $voting_id: ".mysqli_error($conn)."<br>";
            }
            else
            {
                echo "Added $email to voting $voting_id successfully.<br>";
            }
            echo "Operation successful";
        }
    }
}
?>

<input type="button" value="Return to admin panel" onclick="document.location.href='admin_panel.php'" />