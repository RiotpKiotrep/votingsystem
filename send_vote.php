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
        $sql = "INSERT INTO ".$votingdb." (email, candidate) VALUES (?, ?)";

        $stmt = mysqli_stmt_init($conn);

        if (!mysqli_stmt_prepare($stmt, $sql))
        {
            die(mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "ss", $email, $candidate);
        mysqli_stmt_execute($stmt);

        echo "Record saved";
    }
}
else
{
    echo "Missing candidate choice";
    die();
}
?>