<?php
session_start();

include("connection.php");
include("functions.php");

if($_SERVER['REQUEST_METHOD'] == "POST")
{
    $email = $_POST['email'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $password = $_POST['password'];

    if(!empty($email) && !empty($password) && filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        $user_id = random_num(20);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "insert into users (user_id,email,first_name,last_name,password) values ('$user_id','$email','$first_name','$last_name','$hashed_password')";
        mysqli_query($conn, $query);

        $log = "User $user_id with email $email has been registered";
        logger($log);

        header("Location: login.php");
        die;
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
        <div style="font-size: 20px; margin: 10px;">Zarejestruj się:</div>
        <form method="post">
            <label for="email">Email:</label>
            <input id="text" type="text" name="email"><br><br>
            <label for="first_name">Imię:</label>
            <input id="text" type="text" name="first_name"><br><br>
            <label for="last_name">Nazwisko:</label>
            <input id="text" type="text" name="last_name"><br><br>
            <label for="password">Hasło:</label>
            <input id="text" type="password" name="password"><br><br>

            <input id="button" type="submit" value="Zarejestruj się"><br><br>

            <a href="login.php">Zaloguj się</a>
        </form>
    </div>
</body>
</html>