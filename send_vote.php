<?php
$email = $_POST['email'];
$candidate = $_POST['candidate'];
$votingdb = $_POST['votingdb'];

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
        $hashed_email = sha1($email);
        //$query = "select * from available_users where email = '$hashed_email' and voting = '$votingdb'";
        $query = "select * from available_users where email = '$email' and voting = '$votingdb'";
        $result = mysqli_query($conn, $query);
        if($result)
        {
            if($result && mysqli_num_rows($result) == 0)
            {
                echo "Not allowed to vote";
                //header("Location: index.php");
                die;
            }
        }
        $query = "select * from $votingdb where email = '$hashed_email'";
        $result = mysqli_query($conn, $query);
        if($result)
        {
            if($result && mysqli_num_rows($result) > 0)
            {
                echo "Already voted";
                //header("Location: index.php");
                die;
            }
        }

        # encryption test with keys - generating keys
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
        
        // encrypt
        $candidate_encr = sodium_crypto_box($candidate.'|'.$_SERVER['HTTP_REFERER'], $nonce, $sender_keypair);
        echo bin2hex($candidate_encr);
        echo "\n";

        // sign
        /*
        $candidate_signed = sodium_crypto_sign($candidate_encr, $sign_secret);
        echo bin2hex($candidate_signed);
        echo "\n";
        */

        $sql = "INSERT INTO ".$votingdb." (email, candidate) VALUES (?, ?)";

        $stmt = mysqli_stmt_init($conn);

        if (!mysqli_stmt_prepare($stmt, $sql))
        {
            die(mysqli_error($conn));
        }

        $msg = bin2hex($candidate_encr)."|".bin2hex($nonce);
        mysqli_stmt_bind_param($stmt, "ss", $hashed_email, $msg);
        mysqli_stmt_execute($stmt);

        echo "Record saved";
        
        // decrypt test
        /*
        $recver_keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey($server_secret, $client_public);
        $can_decr = sodium_crypto_box_open($candidate_encr, $nonce, $recver_keypair);
        echo $can_decr;
        */
        
        $subject = "Oddałeś głos na stronie votingsystem";
        $message = "Jeśli to nie ty, kliknij tutaj aby usunąć głos: link";
        if(mail($email, $subject, $message))
        {
            echo "Email sent";
        }
        else
        {
            echo "Email not sent";
        }
        

        //header("Location: index.php");
        //die;
    }
}
else
{
    echo "Missing candidate choice";
    die();
}
?>