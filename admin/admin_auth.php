<?php
session_start();

$login_hash = '$2y$10$kFZcVcoBJjEstNWQmOSwue.pTy2Vi2QcaKadLurwrNXkg1snC9DX6';
$password_hash = '$2y$10$IGoD0pr8yuuqYG5uPIojNOwwfXeFpEPdmU5kCfLN6J1EzHMcvUNLi';

if(isset($_GET['m']) && $_GET['m'] == 'session_expired')
{
    echo "Session expired";
}

if($_SERVER['REQUEST_METHOD'] == "POST")
{
    $login = $_POST['login'];
    $password = $_POST['password'];

    if(password_verify($login, $login_hash) && password_verify($password, $password_hash))
    {
        $_SESSION['admin_auth'] = true;
        header('Location: admin_panel.php');
        die;
    }
    else
    {
        echo "Wrong login or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin login page</title>
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
        <div style="font-size: 20px; margin: 10px;">Log in as administrator:</div>
        <form method="post">
            <label for="login">Login:</label>
            <input id="text" type="text" name="login"><br><br>
            <label for="password">Password:</label>
            <input id="text" type="password" name="password"><br><br>

            <input id="button" type="submit" value="Log in"><br><br>
        </form>
    </div>
</body>
</html>