<?php
session_start();

require_once 'functions.php';

if($_SERVER['REQUEST_METHOD'] == "POST")
{
    $email = $_POST['email'];
    $password = $_POST['password'];

    if(!empty($email) && !empty($password) && filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        $pdo = getDB('login_system_db');

        $stmt = $pdo->prepare("SELECT user_id, password, verified FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if($user && password_verify($password, $user['password']) && $user['verified'] == 1)
        {
            $_SESSION['user_id'] = $user['user_id'];
            logger("User $email logged in");
            header("Location: index.php");
            die;
        }

        $log = "User tried logging in with email: $email";
        logger($log);

        echo "Wrong email, password or user isn't verified";
    }  
    else
    {
        $log = "User tried logging in with email: $email";
        logger($log);

        echo "Wrong email, password or user isn't verified";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login page</title>
</head>
<body>
    <style type="text/css">
    
    #text{
        height: 25px;
        border-radius: 5px;
        padding: 4px;
        border: solid thin #aaa;
    }

    #button{
        padding: 10px;
        width: 100px;
        color: white;
        background-color: lightsteelblue;
        border: none;
    }

    #box{
        background-color: lightskyblue;
        margin: auto;
        width: 300px;
        padding: 20px;
    }

    </style>

    <div id="box">
        <div style="font-size: 20px; margin: 10px;">Log in:</div>
        <form method="post">
            <label for="email">Email:</label>
            <input id="text" type="text" name="email"><br><br>
            <label for="password">Password:</label>
            <input id="text" type="password" name="password"><br><br>

            <input id="button" type="submit" value="Log in"><br><br>

            <a href="signup.php">Sign up</a>
        </form>
    </div>
</body>
</html>