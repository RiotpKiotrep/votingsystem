<?php
session_start();
require_once 'functions.php';
$user_data = check_login();
$email = $_POST['email'] ?? $user_data['email'];
$candidate = $_POST['candidate'] ?? null;
$votingdb = $_POST['votingdb'] ?? null;
$signature = $_POST['signature'] ?? null;

if (!$candidate || !$votingdb || !$signature) {
    die("Missing data.");
}

$voting = getVotingConfig($votingdb);
if (!$voting || $voting['voting_ended']) {
    die("Voting is unavailable.");
}

$pdo = getDB('voting_system_db');
$hashed_email = hash('sha256', $email);

// permission check
$stmt = $pdo->prepare("SELECT voted FROM permitted_users WHERE email = ? AND voting = ?");
$stmt->execute([$hashed_email, $votingdb]);
$perm = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$perm) die("Access denied.");
if ($perm['voted'] == 1) die("You have already voted.");

// cryptographic check for signature
$pub = openssl_pkey_get_public(file_get_contents("public.pem"));
$details = openssl_pkey_get_details($pub);
$n = gmp_init(bin2hex($details['rsa']['n']), 16);
$e = gmp_init(bin2hex($details['rsa']['e']), 16);

$expected_hex = hash("sha256", $candidate);
$expected = gmp_init($expected_hex, 16);
$sig = gmp_init($signature, 10);

$verified = gmp_powm($sig, $e, $n);

if (gmp_cmp($verified, $expected) !== 0) {
    die("Invalid signature.");
}

// check whether signature was already used
$stmt = $pdo->prepare("SELECT 1 FROM `$votingdb` WHERE signature = ?");
$stmt->execute([$signature]);
if ($stmt->fetch()) die("This vote signature was already used.");

// generate token for deletion
$token = generate_token();

$server_public = hex2bin("5f9b367104d8ed5e88fa4e410fe529678d55520a1469392dc447a8d41f4ccf49");

$plain_data = $candidate . '|' . ($_SERVER['HTTP_REFERER'] ?? 'direct');

// sealed box encryption
$encrypted_candidate = sodium_crypto_box_seal($plain_data, $server_public);
$encrypted_candidate_hex = bin2hex($encrypted_candidate);

try {
    $pdo->beginTransaction();

    // email for deletion
    $stmt = $pdo->prepare("INSERT INTO `$votingdb` (signature, candidate, token) VALUES (?, ?, ?)");
    $stmt->execute([$signature, $encrypted_candidate_hex, $token]);

    // mark as voted in permission table
    $stmt = $pdo->prepare("UPDATE permitted_users SET voted = 1 WHERE email = ? AND voting = ?");
    $stmt->execute([$hashed_email, $votingdb]);

    $pdo->commit();

    // confirmation email
    $delete_link = "https://localhost/votingsystem/delete_vote.php?v=$votingdb&t=$token";
    $subject = "Vote Confirmation";
    $message = "You have successfully voted. If this wasn't you, use this link to remove your vote: \n$delete_link";
    
    mail($email, $subject, $message);

    echo "Vote successfully sent.";

} catch (Exception $e) {
    $pdo->rollBack();
    logger($e->getMessage());
    die("Transaction failed: " . $e->getMessage());
}