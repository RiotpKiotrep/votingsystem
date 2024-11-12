<?php

function check_login($conn)
{
    if(isset($_SESSION['user_id']))
    {
        $id = $_SESSION['user_id'];
        $query = "select * from users where user_id = '$id' limit 1";
        $result = mysqli_query($conn, $query);
        if($result && mysqli_num_rows($result) > 0)
        {
            $user_data = mysqli_fetch_assoc($result);
            return $user_data;
        }
    }
    // redirect to login page
    header("location: login.php");
    die;
}

function random_num($length)
{
    $text = "";
    if($length < 5)
    {
        $length < 5;
    }
    $len = rand(4, $length);

    for($i=0; $i < $len; $i++)
    {
        $text .= rand(0,9);
    }
    return $text;
}

function blind_sign($message)
{
    /*
    $blind_keypair = sodium_crypto_box_keypair();
    $blind_secret = sodium_crypto_box_secretkey($blind_keypair);
    print(bin2hex($blind_secret));
    print('\n');
    $blind_public = sodium_crypto_box_publickey($blind_keypair);
    print(bin2hex($blind_public));
    print('\n');
    */

    
}