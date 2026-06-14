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
$votingId = isset($_POST['id']) ? (int)$_POST['id'] : null;

if (!$action) {
    echo "Missing parameters.";
    exit;
}

// Find voting
$voting = null;

if ($action !== 'add') {
    if (!$votingId) {
        echo "Missing voting ID.";
        exit;
    }

    foreach ($votings as &$v) {
        if ($v['id'] === $votingId) {
            $voting = &$v;
            break;
        }
    }

    if (!$voting) {
        echo "Voting not found.";
        exit;
    }
}

// only save votings.json when needed
$shouldSaveJson = false;

switch ($action) {

    case 'add':
        if ($_SESSION['admin_role'] === 'AUDITOR') {
            echo "You do not have permission to add votings.";
            break;
        }

        $voting_name = $_POST['voting_name'] ?? '';
        $title       = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $candidates  = $_POST['candidates'] ?? '';
        $expiry_date = $_POST['expiry_date'] ?? '';

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $voting_name)) {
            echo "Invalid voting name.";
            break;
        }

        // forbidden table names
        $forbidden = ['permitted_users', 'voting_tokens'];

        if (in_array(strtolower($voting_name), $forbidden, true)) {
            echo "This voting name is reserved and cannot be used.";
            break;
        }


        foreach ($votings as $v) {
            if ($v['voting_name'] === $voting_name) {
                echo "Voting name already exists.";
                break 2;
            }
        }

        $pdo = getDB('voting_system_db');

        $sql = "CREATE TABLE `$voting_name` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            signature TEXT NOT NULL,
            candidate VARCHAR(256) NOT NULL,
            token VARCHAR(256) NOT NULL
        )";

        try {
            $pdo->exec($sql);

            $new_voting = [
                'id' => count($votings) + 1,
                'voting_name' => $voting_name,
                'title' => $title,
                'description' => $description,
                'candidates' => array_map('trim', explode(',', $candidates)),
                'expiry_date' => $expiry_date,
                'voting_ended' => false
            ];

            $votings[] = $new_voting;
            $shouldSaveJson = true;

            logger("Voting $voting_name added");
            echo "<h2>Voting added successfully.</h2>";

        } catch (PDOException $e) {
            echo "Error creating table: " . $e->getMessage();
        }
        break;

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
