<?php
$email = $_POST['email'];
$candidate = $_POST['candidate'];
$votingdb = $_POST['votingdb'];

include("functions.php");

$log = "User $email has tried sending vote in voting: $votingdb";
logger($log);

$votings_file = file_get_contents('votings.json');
$votings = json_decode($votings_file, true);
if(json_last_error() !== JSON_ERROR_NONE)
{
    die('Error decoding JSON: '.json_last_error_msg());
}
$voting = null;
foreach($votings as $v)
{
    if($v['voting_name'] === $votingdb)
    {
        $voting_name = $v;
        break;
    }
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
    $host = "localhost";
    $dbUsername = "root";
    $dbPassword = "";
    $dbName = "voting_system_db";

    $conn = new mysqli($host, $dbUsername, $dbPassword, $dbName);
    if(mysqli_connect_error())
    {
        die('Connect error('. mysqli_connect_errno().')'. mysqli_connect_error());
    }
    else
    {
        $hashed_email = hash('sha256',$email);
        $query = "select * from permitted_users where email = '$hashed_email' and voting = '$votingdb'";
        $result = mysqli_query($conn, $query);
        if($result)
        {
            if($result && mysqli_num_rows($result) == 0)
            {
                $log = "User not allowed to vote";
                logger($log);
                
                header("Refresh:5; url=index.php");

                echo "Not allowed to vote";

                die;
            }
        }
        $query = "select * from $votingdb where email = '$hashed_email'";
        $result = mysqli_query($conn, $query);
        if($result)
        {
            if($result && mysqli_num_rows($result) > 0)
            {
                $log = "User has already voted";
                logger($log);

                header("Refresh:5; url=index.php");
                
                echo "Already voted";

                die;
            }
        }

        # key generation for the purpose of showcase - should be deleted in real version
        /*
        $cli_keypair = sodium_crypto_box_keypair();
        $cli_secret = sodium_crypto_box_secretkey($cli_keypair);
        print(bin2hex($cli_secret));
        print('\n');
        $cli_public = sodium_crypto_box_publickey($cli_keypair);
        print(bin2hex($cli_public));
        print('\n');

        
        $srv_keypair = sodium_crypto_box_keypair();
        $srv_secret = sodium_crypto_box_secretkey($srv_keypair);
        print(bin2hex($srv_secret));
        print('\n');
        $srv_public = sodium_crypto_box_publickey($srv_keypair);
        print(bin2hex($srv_public));
        print('\n');
        */
        
        $client_secret = hex2bin("5b589e9aa4919025f075a46130d4aa77d35b62698269f3f6c1f07c644d7f499f");
        $client_public = hex2bin("ce4ddb4ac70feb390b29722f70adf06ba346920db3baef804f9514a87eb35c13"); // <- do usunięcia i przechowania
        $server_secret = hex2bin("c13f4d014046f5f572a1edd938f2b8b2765c922611c7136dde463db32e9d4995"); // <- w pliku administratora
        $server_public = hex2bin("5f9b367104d8ed5e88fa4e410fe529678d55520a1469392dc447a8d41f4ccf49");
        $sign_secret = hex2bin("0415d868857d885da551db06ab83adb41ba37df64493eddafafe14846554d117f87f6ae489c20fa4c706a8490dd0d07d9fa13a699ca4d8389de3594eadf54740");
        $sign_public = hex2bin("f87f6ae489c20fa4c706a8490dd0d07d9fa13a699ca4d8389de3594eadf54740");
        $sender_keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey($client_secret, $server_public);
        $nonce = \random_bytes(\SODIUM_CRYPTO_BOX_NONCEBYTES);
        
        $candidate_encr = sodium_crypto_box($candidate.'|'.$_SERVER['HTTP_REFERER'], $nonce, $sender_keypair);

        $token = generate_token();

        $sql = "INSERT INTO ".$votingdb." (email, candidate, token) VALUES (?, ?, ?)";

        $stmt = mysqli_stmt_init($conn);

        if (!mysqli_stmt_prepare($stmt, $sql))
        {
            die(mysqli_error($conn));
        }

        $msg = bin2hex($candidate_encr)."|".bin2hex($nonce);
        mysqli_stmt_bind_param($stmt, "sss", $hashed_email, $msg, $token);
        mysqli_stmt_execute($stmt);
        
        $log = "Vote successfully sent";
        logger($log);

        $delete_link = "https://localhost/votingsystem/delete_vote.php?v=$votingdb&t=$token";
        $subject = "You voted on votingsystem";
        $message = "If the action wasn't performed by you, click this link to remove vote: \n$delete_link";
        if(mail($email, $subject, $message))
        {
            $log = "Confirmation email sent";
            logger($log);
        }
        else
        {
            $log = "Confirmation email not sent";
            logger($log);
        }
        
        header("Refresh:5; url=index.php");
        echo "Vote successfully sent";
        die;
    }
}
else
{
    echo "Missing candidate choice";
    die();
}
?>