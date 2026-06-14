<?php
session_start();
require_once '../../functions.php';
check_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Invalid request.";
    exit;
}

$votings  = json_decode(file_get_contents('../../votings.json'), true);
$action   = $_POST['action'] ?? null;
$votingId = (int)($_POST['id'] ?? 0);

if (!$action || !$votingId) {
    echo "Missing parameters.";
    exit;
}

// Find voting
$found = false;
foreach ($votings as &$voting) {
    if ($voting['id'] === $votingId) {
        $found = true;
        break;
    }
}

if (!$found) {
    echo "Voting not found.";
    exit;
}

// only save votings.json when needed
$shouldSaveJson = false;

switch ($action) {

    case 'end':
        if ($voting['voting_ended']) {
            echo "Voting already ended.";
            break;
        }

        $voting['voting_ended'] = true;
        $shouldSaveJson = true;

        logger("Voting {$voting['voting_name']} ended");
        echo "Voting ended successfully.";
        break;


    case 'resume':
        if ($_SESSION['admin_role'] !== 'SUPERADMIN') {
            echo "Only Superadmin can resume voting.";
            break;
        }

        if (!$voting['voting_ended']) {
            echo "Voting is already active.";
            break;
        }

        $voting['voting_ended'] = false;
        $shouldSaveJson = true;

        logger("Voting {$voting['voting_name']} resumed by Superadmin");
        echo "Voting resumed successfully.";
        break;


    case 'change_date':
        $newDate = $_POST['new_date'] ?? null;

        if (!$newDate) {
            echo "Missing new date.";
            break;
        }

        $voting['expiry_date'] = $newDate;
        $shouldSaveJson = true;

        logger("Voting {$voting['voting_name']} end date changed to $newDate");
        echo "End date updated successfully.";
        break;


    case 'add_users':
        $emails = $_POST['emails'] ?? "";

        if (!$emails) {
            echo "No emails provided.";
            break;
        }

        if ($voting['voting_ended']) {
            echo "Cannot add users to an ended voting.";
            break;
        }

        $pdo = getDB('voting_system_db');

        $emailArray = array_filter(array_map('trim', explode(",", $emails)));
        if (empty($emailArray)) {
            echo "No valid emails provided.";
            break;
        }

        $stmt = $pdo->prepare("INSERT IGNORE INTO permitted_users (email, voting) VALUES (?, ?)");

        foreach ($emailArray as $email) {
            // basic sanity check; you already validate on frontend
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $hashed_email = hash('sha256', $email);
            $stmt->execute([$hashed_email, $voting['voting_name']]);
            logger("Added $email to voting {$voting['voting_name']}");
        }

        logger("Added users to voting {$voting['voting_name']}");
        echo "<h2>Users added successfully.</h2>";
        echo "<p>" . count($emailArray) . " users processed.</p>";
        break;


    default:
        echo "Unknown action.";
        break;
}

// save changes to votings.json
if ($shouldSaveJson) {
    file_put_contents('../../votings.json', json_encode($votings, JSON_PRETTY_PRINT));
}
