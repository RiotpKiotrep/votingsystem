<?php
session_start();
require_once '../functions.php';
check_admin_login();

header('Content-Type: application/json');

$adminEmail = $_SESSION['admin_email'];
$adminRole  = $_SESSION['admin_role'];
$votingId   = $_GET['voting_id'] ?? null;

$allowed = [];

// SUPERADMIN (full access)
if ($adminRole === 'SUPERADMIN') {
    $allowed = [
        "add_voting",
        "add_users",
        "manage_voting",
        "count_votes",
        "admin_management",
        "add_admins"
    ];
    echo json_encode($allowed);
    exit;
}

// AUDITOR (can only display count)
if ($adminRole === 'AUDITOR') {

    $conn = getDB('admin_system_db');
    $stmt = $conn->prepare("
        SELECT can_count_votes
        FROM permitted_admins
        WHERE email = ? AND voting_id = ?
        LIMIT 1
    ");
    $stmt->execute([$adminEmail, $votingId]);
    $perm = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($perm && $perm['can_count_votes']) {
        echo json_encode(["count_votes"]);
    } else {
        echo json_encode([]); // no access
    }
    exit;
}

// VOTING_ADMIN
$conn = getDB('admin_system_db');

$stmt = $conn->prepare("
    SELECT can_add_users, can_manage_voting, can_count_votes, can_add_admins, is_creator
    FROM permitted_admins
    WHERE email = ? AND voting_id = ?
    LIMIT 1
");
$stmt->execute([$adminEmail, $votingId]);
$perm = $stmt->fetch(PDO::FETCH_ASSOC);

// no perm
if (!$perm) {
    echo json_encode([]);
    exit;
}

// assign permissions
if ($perm['can_add_users'])   $allowed[] = "add_users";
if ($perm['can_manage_voting'])  $allowed[] = "manage_voting";
if ($perm['can_count_votes']) $allowed[] = "count_votes";

// adding admins to voting only enabled for creator
if ($perm['is_creator']) {
    $allowed[] = "add_admins";
}

echo json_encode($allowed);
