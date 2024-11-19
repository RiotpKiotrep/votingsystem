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
        $sql = "select * from users where email = ? limit 1";
        $stmt = mysqli_stmt_init($conn);

        if (!mysqli_stmt_prepare($stmt, $sql))
        {
            die(mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if($result)
        {
            if($result && mysqli_num_rows($result) > 0)
            {
                $user_data = mysqli_fetch_assoc($result);

                if(password_verify($password, $user_data['password']) && $user_data['verified']==1)
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