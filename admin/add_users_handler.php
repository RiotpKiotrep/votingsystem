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
    $emails = $_POST['emails'] ?? '';
    $voting_id = $_POST['id'] ?? '';
    if(empty($emails) || empty($voting_id))
    {
        die("No email addresses provided");
    }

    $voting = getVotingConfigById($voting_id);
    if (!$voting) die("Voting not found");

    $emails = array_map('trim', explode(',', $emails));
    
    $pdo = getDB('voting_system_db');
    
    $stmt = $pdo->prepare("INSERT INTO permitted_users (email, voting) VALUES (?, ?)");

    foreach ($emails as $email)
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            echo "Invalid email format: $email. Skipping.<br>";
            continue;
        }

        $hashed_email = hash('sha256', $email);

        try
        {
            $stmt->execute([$hashed_email, $voting['voting_name']]);
            logger("Added $email to voting " . $voting['voting_name']);
            echo "Added $email successfully.<br>";
        }
        catch (PDOException $e)
        {
            echo "Failed to add $email: " . $e->getMessage() . "<br>";
        }
    }
}
?>

<input type="button" value="Return to admin panel" onclick="document.location.href='admin_panel.php'" />