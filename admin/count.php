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

require_once '../functions.php';

$votings_file = file_get_contents('../votings.json');
$votings = json_decode($votings_file, true);
$keys = require('../keys.php');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css">
    <title>Count</title>
</head>
<body>
    <div class="header"><h1>Count votes</h1></div>
    <div class="menu"> 
    <form method="post">
            <label for="voting_id">Choose voting:</label>
            <select id="voting_id" name="id" required>
                <?php foreach($votings as $voting):?>
                    <option value="<?php echo $voting['id'];?>">
                        <?php echo $voting['title'];?>
                    </option>
                <?php endforeach;?>
            </select>
            <button type="submit" name="submit">Count votes</button>
        </form>


<?php

if(isset($_POST['id']))
{
    $voting_id = (int)$_POST['id'];

    $voting = getVotingConfigById($voting_id);
    $votingdb = $voting['voting_name'] ?? null;

    if($votingdb === null)
    {
        echo "Wrong data";
    }
    else
    {
        $pdo = getDB();
        $now = new DateTime();
        $expiry_date = new DateTime($voting['expiry_date']);
        if (!$voting['voting_ended'] && $now < $expiry_date)
        {
            echo "This voting has not ended yet.";
        }
        else
        {
            $pdo = getDB();

            $log = "Displayed vote count for: $votingdb";
            logger($log);

            $recver_keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey($keys['server_secret'], $keys['client_public']);

            $stmt = $pdo->prepare("SELECT candidate FROM `$votingdb`");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $count = [];
            $total_count = 0;
            $error_count = 0;
                
            foreach($rows as $row)
            {
                $params = explode("|", $row['candidate']);
                if(count($params) !== 2)
                { 
                    $error_count++;
                    continue;
                }

                try
                {
                    $decrypted = sodium_crypto_box_open(hex2bin($params[0]), hex2bin($params[1]), $recver_keypair);
                }
                catch(Exception $e)
                {
                    $error_count += 1;
                    continue;
                }
                //echo $decrypted;
                $candidate = explode("|", $decrypted);
                if($candidate[1] == "http://localhost/votingsystem/vote_page.php?".$voting_id)
                {
                    $name = $candidate[0];
                    $count[$name] = ($count[$name] ?? 0) + 1;
                    $total_count += 1;
                    //echo "<br>".$count[$candidate[0]]."<br>";
                }
                else
                {
                    $error_count += 1;
                }
            }
            foreach($count as $can => $num)
            {
                echo $can.": ".$num."<br>";
            }

            $stmt = $pdo->prepare("SELECT count(email) as email_count FROM permitted_users WHERE voting = ?");
            $stmt->execute([$votingdb]);
            $allowed = $stmt->fetch(PDO::FETCH_ASSOC);

            echo "<br>Amount of users allowed to vote: ".$allowed['email_count']."<br>";

            if($total_count < $allowed['email_count']) echo $allowed['email_count']-$total_count." users didn't vote<br>Recommendation: contact voting participants<br><br>";

            echo "Errors: ".$error_count."<br>";
            if($error_count>0) echo "Recommendation: check database entries";
        }
    }
}

?>

        <br>
        <button class="return" onclick="document.location.href='admin_panel.php'">Return to admin panel</button>
    </div>
</body>
</html>