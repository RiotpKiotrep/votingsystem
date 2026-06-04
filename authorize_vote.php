<?php
session_start();
require_once 'functions.php';
$user_data = check_login();
$email = $user_data['email'];
$hashed_email = hash('sha256', $email);

$votingdb = $_POST['votingdb'] ?? null;
$blinded_vote = $_POST['blinded_vote'] ?? null;

if (!$votingdb || !$blinded_vote) {
    die("Missing data");
}

$pdo = getDB('voting_system_db');

// Check if permitted and hasn't finished voting yet
$stmt = $pdo->prepare("SELECT voted FROM permitted_users WHERE email = ? AND voting = ?");
$stmt->execute([$hashed_email, $votingdb]);
$res = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$res)
    die("Not allowed to vote");
if ($res['voted'] == 1)
    die("Already voted");

// Sign the blinded vote
$blinded = gmp_init($blinded_vote, 10);
$priv = openssl_pkey_get_private(file_get_contents("private.pem"));
$details = openssl_pkey_get_details($priv);
$n = gmp_init(bin2hex($details['rsa']['n']), 16);
$d = gmp_init(bin2hex($details['rsa']['d']), 16);

$signed = gmp_powm($blinded, $d, $n);
echo gmp_strval($signed);