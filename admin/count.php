<?php
session_start();

if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true)
{
    header("Location: admin_auth.php");
    die;
}

$inactive_time_limit = 1*60; // 1 * 60 seconds
if(isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $inactive_time_limit)
{
    session_unset();
    session_destroy();
    header("Location: admin_auth.php?m=session_expired");
    die;
}
$_SESSION['last_activity'] = time();

$votings_file = file_get_contents('../votings.json');
$votings = json_decode($votings_file, true);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Count</title>
</head>
<body>
    <h1>Count votes</h1>
    <form method="post">
        <label for="voting_id">Choose voting:</label>
        <select id="voting_id" name="id" required>
            <?php foreach($votings as $voting):?>
                <option value="<?php echo $voting['id'];?>">
                    <?php echo $voting['title'];?>
                </option>
            <?php endforeach;?>
        </select><br><br>
        <button type="submit" name="submit">Count votes</button>
    </form>
    <br>


<?php

if(isset($_POST['id']))
{
    $voting_id = (int)$_POST['id'];

    if(json_last_error() !== JSON_ERROR_NONE)
    {
        die('Error decoding JSON: '.json_last_error_msg());
    }

    $votingdb = null;
    foreach($votings as $voting)
    {
        if($voting['id'] === $voting_id)
        {
            $votingdb = $voting['voting_name'];
            break;
        }
    }

    if($votingdb === null)
    {
        echo "Wrong data";
    }
    elseif (!$voting['voting_ended'])
    {
        echo "This voting has not ended yet.";
    }
    else
    {
        $host = "localhost";
        $dbUsername = "root";
        $dbPassword = "";
        $dbName = "voting_system_db";

        include("../functions.php");

        $conn = new mysqli($host, $dbUsername, $dbPassword, $dbName);
        if(mysqli_connect_error())
        {
            die('Connect error('. mysqli_connect_errno().')'. mysqli_connect_error());
        }
        else
        {
            $log = "Displayed vote count for: $votingdb";
            logger($log);

            $client_public = hex2bin("ce4ddb4ac70feb390b29722f70adf06ba346920db3baef804f9514a87eb35c13");
            $server_secret = hex2bin("c13f4d014046f5f572a1edd938f2b8b2765c922611c7136dde463db32e9d4995");
            $recver_keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey($server_secret, $client_public);

            $query = "select candidate from $votingdb";
            $result = mysqli_query($conn, $query);
            if($result)
            {
                $count = [];
                $total_count = 0;
                $error_count = 0;
                while($row = mysqli_fetch_assoc($result))
                {
                    $params = explode("|", $row['candidate']);
                    if(!array_key_exists(1, $params))
                    {
                        $error_count += 1;
                        continue;
                    }
                    try
                    {
                        $can_decr = sodium_crypto_box_open(hex2bin($params[0]), hex2bin($params[1]), $recver_keypair);
                    }
                    catch(Exception $e)
                    {
                        $error_count += 1;
                        continue;
                    }
                    //echo $can_decr;
                    $candidate = explode("|", $can_decr);
                    if($candidate[1] == "http://localhost/votingsystem/vote_page.php?".$voting_id)
                    {
                        if(!array_key_exists($candidate[0], $count))
                        {
                            $count[$candidate[0]] = 1;
                        }
                        else
                        {
                            $count[$candidate[0]] += 1;
                        }
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

                $query = "select count(email) as email_count from available_users where voting = '$votingdb'";
                $result = mysqli_query($conn, $query);
                if($result)
                {
                    $email_count = mysqli_fetch_assoc($result);
                    echo "<br>Amount of users allowed to vote: ".$email_count['email_count']."<br>";
                }
                if($total_count < $email_count['email_count']) echo $email_count['email_count']-$total_count." users didn't vote<br>Recommendation: contact voting participants<br><br>";

                echo "Errors: ".$error_count."<br>";
                if($error_count>0) echo "Recommendation: check database entries";
            }
        }
    }
}

?>

<br><br>
<input type="button" value="Return to admin panel" onclick="document.location.href='admin_panel.php'" />
</body>
</html>