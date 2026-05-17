<?php
session_start();

require_once 'functions.php';

if($_SERVER['REQUEST_METHOD'] == "POST")
{
    $email = $_POST['email'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $password = $_POST['password'];

    if(!validate_password($password))
    {
        echo "Password requirements:<br>- at least 8 characters<br>- at least one uppercase letter<br>- at least one lowercase letter<br>- at least one number<br>- at least one special symbol<br>";
    }
    elseif(!empty($email) && !empty($password) && filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        $pdo = getDB('login_system_db');
        // check if user exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if($stmt->fetchColumn() > 0)
        {
            echo "User with this email already exists";
        }
        else
        {
            // generate ID and add user
            $user_id = random_num(20);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $token = generate_token();
    
            $sql = "INSERT INTO users (user_id, email, first_name, last_name, password, verified, token) VALUES (?, ?, ?, ?, ?, 0, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $email, $first_name, $last_name, $hashed_password, $token]);
    
            $log = "User $user_id with email $email has been registered";
            logger($log);
    
            $verify_link = "https://localhost/votingsystem/verify.php?t=$token";
            $subject = "Verify your account";
            $message = "Hello $first_name,\nclick on the link below to verify your account:\n$verify_link.";
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
    
            header("Location: login.php");
            die;
        }
    }
    else
    {
        $log = "User has tried using incorrect email format: $email";
        logger($log);

        echo "Incorrect email format";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup page</title>
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
        <div style="font-size: 20px; margin: 10px;">Sign up:</div>
        <form method="post">
            <label for="email">Email:</label>
            <input id="text" type="text" name="email"><br><br>
            <label for="first_name">First name:</label>
            <input id="text" type="text" name="first_name"><br><br>
            <label for="last_name">Last name:</label>
            <input id="text" type="text" name="last_name"><br><br>
            <label for="password">Password:</label>
            <input id="text" type="password" name="password"><br><br>

            <input id="button" type="submit" value="Sign up"><br><br>

            <a href="login.php">Log in</a>
        </form>
    </div>
</body>
</html>