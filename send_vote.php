<?php
require_once 'functions.php';

$email = $_POST['email'];
$candidate = $_POST['candidate'];
$votingdb = $_POST['votingdb'];

$log = "User $email has tried sending vote in voting: $votingdb";
logger($log);

$voting = getVotingConfig($votingdb);
if(!$voting)
{
    $log = "Tried sending vote into invalid voting";
    logger($log);
    header("Refresh:5; url=index.php");
    echo "Voting doesn't exist";
}

$now = new DateTime();
$expiry_date = new DateTime($voting['expiry_date']);

if(!$voting['voting_ended'] && $now >= $expiry_date)
{
    $log = "Tried sending vote into expired voting";
    logger($log);
    header("Refresh:5; url=index.php");
    echo "Voting has expired";
    die;
}

if($voting && $voting['voting_ended'] === true)
{
    $log = "Tried sending vote into ended voting";
    logger($log);
    header("Refresh:5; url=index.php");
    echo "Voting has already ended";
    die;
}

if (!empty($candidate))
{
    $pdo = getDB();

    $hashed_email = hash('sha256',$email);

    // permission check
    $stmt = $pdo->prepare("SELECT id FROM permitted_users WHERE email = ? AND voting = ?");
    $stmt->execute([$hashed_email, $votingdb]);
    if ($stmt->rowCount() == 0) {
        logger("User $email not allowed to vote in $votingdb");
        die("Not allowed to vote.");
    }

    // voted already check
    $stmt = $pdo->prepare("SELECT id FROM `$votingdb` WHERE email = ?");
    $stmt->execute([$hashed_email]);
    if ($stmt->rowCount() > 0) {
        logger("User $email has already voted in $votingdb");
        die("Already voted.");
    }

    // blind signature
    $signature = $_POST['signature'] ?? null;
    if(!$signature) die("Signature missing.");

    $public_key = GetPublicKey();
    if(!$public_key->verify($candidate, base64_decode($signature)))
    {
        $log = "Invalid signature by $email";
        logger($log);
        die("Invalid signature.");
    }

    // send vote
    $token = generate_token();
    
    $stmt = $pdo->prepare("INSERT INTO `$votingdb` (signature, candidate, token) VALUES (?, ?, ?)");
    $stmt->execute([$signature, $candidate, $token]);

    logger("Vote successfully sent by $email");

    // send confirmation email
    $delete_link = "https://localhost/votingsystem/delete_vote.php?v=$votingdb&t=$token";
    $subject = "You voted on votingsystem";
    $message = "If the action wasn't performed by you, click this link to remove vote: \n$delete_link";
    
    if(mail($email, $subject, $message)) {
        logger("Confirmation email sent to $email");
    } else {
        logger("Confirmation email failed to send to $email");
    }
    
    echo "Vote successfully sent.";
    header("Refresh:5; url=index.php");
    die;
}
else
{
    echo "Missing candidate choice";
    die();
}
?>