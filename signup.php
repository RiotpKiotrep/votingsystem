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
        // check if user exists
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $stmt = mysqli_stmt_init($conn);
        
        if (!mysqli_stmt_prepare($stmt, $sql))
        {
            die(mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $count = mysqli_fetch_assoc($result);
        if($count['count'] > 0)
        {
            echo "User with this email already exists";
            mysqli_stmt_close($stmt);
            die;
        }

        mysqli_stmt_close($stmt);
        
        // generate ID and add user
        $user_id = random_num(20);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $token = generate_token();

        $sql = "INSERT INTO users (user_id, email, first_name, last_name, password, verified, token) VALUES (?, ?, ?, ?, ?, 0, ?)";
        $stmt = mysqli_stmt_init($conn);
        
        if (!mysqli_stmt_prepare($stmt, $sql))
        {
            die(mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "ssssss", $user_id, $email, $first_name, $last_name, $hashed_password, $token);
        mysqli_stmt_execute($stmt);

        $log = "User $user_id with email $email has been registered";
        logger($log);

        $verify_link = "https://localhost/votingsystem/verify.php?t=$token";
        $subject = "Zweryfikuj swoje konto";
        $message = "Witaj $first_name,\nkliknij w poniższy link, aby zweryfikować swoje konto:\n$verify_link.";
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