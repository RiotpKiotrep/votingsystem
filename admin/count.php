<?php
session_start();

use phpseclib3\Crypt\RSA;

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

            $count = [];
            $total_count = 0;
            $error_count = 0;
                
            // rsa keys for gmp verification
            $pub_content = file_get_contents('../public.pem');
            $pub_info = openssl_pkey_get_details(openssl_pkey_get_public($pub_content));
            $n = gmp_init(bin2hex($pub_info['rsa']['n']), 16);
            $e = gmp_init(bin2hex($pub_info['rsa']['e']), 16);

            // sodium key for decryption
            $server_secret = $keys['server_secret'];
            $server_public = sodium_crypto_box_publickey_from_secretkey($server_secret);

            $server_keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey(
                $server_secret,
                $server_public
            );


            // fetch signature and candidate
            $stmt = $pdo->prepare("SELECT signature, candidate FROM `$votingdb`");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach($rows as $row)
            {
                try {
                    // decrypt
                    $hex = $row['candidate'];

                    if (!ctype_xdigit($hex)) {
                        throw new Exception("Candidate field is not valid hex");
                    }

                    $encrypted = hex2bin($hex);
                    if ($encrypted === false) {
                        throw new Exception("hex2bin failed");
                    }

                    $decrypted = sodium_crypto_box_seal_open($encrypted, $server_keypair);
                    if ($decrypted === false) {
                        throw new Exception("Decryption failed");
                    }

                    // plit candidate name from referer
                    $parts = explode("|", $decrypted);
                    if (count($parts) < 2) throw new Exception("Malformed data");
                    
                    $candidate_name = $parts[0];
                    $referer = $parts[1];

                    // verify blind signature
                    $sig = gmp_init($row['signature'], 10);
                    $expected_hash = gmp_init(hash("sha256", $candidate_name), 16);
                    
                    // rsa check: s^e mod n == hash(candidate)
                    $actual_hash = gmp_powm($sig, $e, $n);

                    if (gmp_cmp($actual_hash, $expected_hash) !== 0) {
                        $error_count++;
                        logger("Signature mismatch in $votingdb");
                        continue;
                    }

                    // verify referer
                    if (strpos($referer, "vote_page.php?id=" . $voting_id) !== false) {
                        $count[$candidate_name] = ($count[$candidate_name] ?? 0) + 1;
                        $total_count++;
                    } else {
                        logger("Referer mismatch: " . $referer);
                        $error_count++;
                    }

                } catch (Exception $exception) {
                    $error_count++;
                    logger("Error processing vote: " . $exception->getMessage());
                    continue;
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