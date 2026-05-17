<?php

require_once 'functions.php';

$votingdb = $_GET['v'] ?? null;
$token = $_GET['t'] ?? null;

if($votingdb && $token)
{
    if (!getVotingConfig($votingdb))
    {
        die("Link is invalid (Voting not found).");
    }

    $pdo = getDB('voting_system_db');
    
    $stmt = $pdo->prepare("DELETE FROM `$votingdb` WHERE token = ?");
    $stmt->execute([$token]);

    if ($stmt->rowCount() > 0)
    {
        logger("Vote deleted from $votingdb through email link");
        echo "Vote has been deleted.";
    }
    else
    {
        logger("Attempted to delete non-existent or invalid vote in $votingdb (Token: $token)");
        echo "The link is invalid or the vote has already been deleted.";
    }
}
else
{
    echo "Missing parameters.";
}


?>