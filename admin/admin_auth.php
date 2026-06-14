<?php
session_start();

require_once '../functions.php';

if(isset($_GET['m']) && $_GET['m'] == 'session_expired')
{
    echo "Session expired";
}

if($_SERVER['REQUEST_METHOD'] == "POST")
{
    $email = $_POST['email'];
    $password = $_POST['password'];

    if(!empty($email) && !empty($password) && filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        $pdo = getDB('admin_system_db');

        $stmt = $pdo->prepare("SELECT admin_id, email, password, role, verified FROM admins WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if($admin && password_verify($password, $admin['password']) && $admin['verified'] == 1)
        {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['last_activity'] = time();
            $_SESSION['ua'] = $_SERVER['HTTP_USER_AGENT'];
            logger("Admin $email logged in");
            header("Location: admin_panel.php");
            die;
        }

        $log = "Admin tried logging in with email: $email";
        logger($log);

        echo "Wrong email, password or admin isn't verified";
    }  
    else
    {
        $log = "Admin tried logging in with email: $email";
        logger($log);

        echo "Wrong email, password or admin isn't verified";
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
            <label for="login">Email:</label>
            <input id="text" type="text" name="email"><br><br>
            <label for="password">Password:</label>
            <input id="text" type="password" name="password"><br><br>

            <input id="button" type="submit" value="Log in"><br><br>
        </form>
    </div>
</body>
</html>