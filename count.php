<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Count</title>
</head>
<body>
    <form method="post">
        <label for="votingdb">Voting name:</label>
        <input type="text" name="votingdb"><br>
        <label for="voting_id">Voting ID:</label>
        <input type="text" name="voting_id"><br>
        <input type="submit" name="submit"><br>
    </form>
</body>
</html>

<?php

if(isset($_POST['votingdb'], $_POST['voting_id']))
{
    $votingdb = $_POST['votingdb'];
    $voting_id = $_POST['voting_id'];

    $votings_file = file_get_contents('votings.json');
    $votings = json_decode($votings_file, true);
    if(json_last_error() !== JSON_ERROR_NONE)
    {
        die('Error decoding JSON: '.json_last_error_msg());
    }

    $exists = false;
    foreach($votings as $voting)
    {
        if($voting['voting_name'] === $votingdb && $voting['id'] == $voting_id)
        {
            $exists = true;
            break;
        }
    }
    if(!$exists)
    {
        echo "Błędne dane";
        die;
    }

    $host = "localhost";
    $dbUsername = "root";
    $dbPassword = "";
    $dbName = "voting_system_db";

    include("functions.php");

    $conn = new mysqli($host, $dbUsername, $dbPassword, $dbName);
    if(mysqli_connect_error())
    {
        die('Connect error('. mysqli_connect_errno().')'. mysqli_connect_error());
    }
    else
    {
        //$votingdb = "voting1";
        //$voting_id = "1";

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
                echo "<br>Ilość osób upoważnionych do głosowania: ".$email_count['email_count']."<br>";
            }
            if($total_count < $email_count['email_count']) echo $email_count['email_count']-$total_count." osób nie zagłosowało<br>Zalecany kontakt z osobami upoważnionymi do głosowania<br><br>";

            echo "Błędy: ".$error_count."<br>";
            if($error_count>0) echo "Zalecany przegląd wpisów w bazie";
        }
    }
}

?>