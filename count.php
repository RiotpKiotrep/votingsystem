<?php

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
    $votingdb = "voting1";
    $voting_id = "1";
    $client_public = hex2bin("ce4ddb4ac70feb390b29722f70adf06ba346920db3baef804f9514a87eb35c13");
    $server_secret = hex2bin("c13f4d014046f5f572a1edd938f2b8b2765c922611c7136dde463db32e9d4995");
    $recver_keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey($server_secret, $client_public);
    
    $query = "select candidate from $votingdb";
    $result = mysqli_query($conn, $query);
    if($result)
    {
        $count = [];
        while($row = mysqli_fetch_assoc($result))
        {
            $params = explode("|", $row['candidate']);
            $can_decr = sodium_crypto_box_open(hex2bin($params[0]), hex2bin($params[1]), $recver_keypair);
            //echo $can_decr;
            $candidate = explode("|", $can_decr);
            if($candidate[1] == "http://localhost/votingsystem/vote_page.php?".$voting_id)
            {
                //echo $candidate[0];
                
                if(!array_key_exists($candidate[0], $count))
                {
                    $count[$candidate[0]] = 1;
                }
                else
                {
                    $count[$candidate[0]] += 1;
                }

                //echo "<br>".$count[$candidate[0]]."<br>";
            }
        }
        foreach($count as $can => $num)
        {
            echo $can.": ".$num."<br>";
        }
    }
}

?>