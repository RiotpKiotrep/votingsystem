<?php
session_start();

include("connection.php");
include("functions.php");

if($_SERVER['REQUEST_METHOD'] == "POST")
{
    $email = $_POST['email'];
    $password = $_POST['password'];

    if(!empty($email) && !empty($password) && filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        $query = "select * from users where email = '$email' limit 1";
        $result = mysqli_query($conn, $query);

        if($result)
        {
            if($result && mysqli_num_rows($result) > 0)
            {
                $user_data = mysqli_fetch_assoc($result);

                if(password_verify($password, $user_data['password']))
                {
                    $_SESSION['user_id'] = $user_data['user_id'];

                    $log = "User $email logged in";
                    logger($log);

                    header("Location: index.php");
                    die;
                }
            }
        }

        $log = "User tried logging in with email: $email";
        logger($log);

        echo "Wrong email or password";
    }  
    else
    {
        $log = "User tried logging in with email: $email";
        logger($log);

        echo "Wrong email or password";
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
        <div style="font-size: 20px; margin: 10px;">Zaloguj się:</div>
        <form method="post">
            <label for="email">Email:</label>
            <input id="text" type="text" name="email"><br><br>
            <label for="password">Password:</label>
            <input id="text" type="password" name="password"><br><br>

            <input id="button" type="submit" value="Zaloguj się"><br><br>

            <a href="signup.php">Zarejestruj się</a>
        </form>
    </div>
</body>
</html>