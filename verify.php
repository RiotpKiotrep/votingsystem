<?php

if(isset($_GET['t']))
{
    include("connection.php");

    $token = $_GET['t'];

    $sql = "SELECT * FROM users WHERE token = ?";
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql))
    {
        die(mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if($result && mysqli_num_rows($result) > 0)
    {
        $user_data = mysqli_fetch_assoc($result);

        $sql = "UPDATE users SET verified = 1 WHERE user_id = ?";
        $stmt = mysqli_stmt_init($conn);

        if (!mysqli_stmt_prepare($stmt, $sql))
        {
            die(mysqli_error($conn));
        }

        $user_id = $user_data['user_id'];
        mysqli_stmt_bind_param($stmt, "s", $user_id);
        mysqli_stmt_execute($stmt);


        $log = "User with ID $user_id verified";

        echo "Account verified";
    }
    else
    {
        echo "Wrong token";
    }
}

?>