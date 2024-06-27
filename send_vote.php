<?php
$email = $_POST['email'];
$candidate = $_POST['candidate'];
$votingdb = $_POST['votingdb'];

if (!empty($candidate))
{
    $host = "localhost";
    $dbUsername = "root";
    $dbPassword = "";
    $dbName = "voting_system_db";

    $conn = new mysqli($host, $dbUsername, $dbPassword, $dbName);
    if(mysqli_connect_error())
    {
        die('Connect error('. mysqli_connect_errno().')'. mysqli_connect_error());
    }
    else
    {
        $query = "select * from available_users where email = '$email' and voting = '$votingdb'";
        $result = mysqli_query($conn, $query);
        if($result)
        {
            if($result && mysqli_num_rows($result) == 0)
            {
                echo "Not allowed to vote";
                //header("Location: index.php");
                die;
            }
        }
        $query = "select * from $votingdb where email = '$email'";
        $result = mysqli_query($conn, $query);
        if($result)
        {
            if($result && mysqli_num_rows($result) > 0)
            {
                echo "Already voted";
                //header("Location: index.php");
                die;
            }
        }


        $sql = "INSERT INTO ".$votingdb." (email, candidate) VALUES (?, ?)";

        $stmt = mysqli_stmt_init($conn);

        if (!mysqli_stmt_prepare($stmt, $sql))
        {
            die(mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "ss", $email, $candidate);
        mysqli_stmt_execute($stmt);

        echo "Record saved";
        
        /*
        $subject = "Oddałeś głos na stronie votingsystem";
        $message = "Jeśli to nie ty, kliknij tutaj aby usunąć głos: link";
        mail($email, $subject, $message);
        */

        //header("Location: index.php");
        //die;
    }
}
else
{
    echo "Missing candidate choice";
    die();
}
?>