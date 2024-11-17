<?php
if(isset($_GET['v'], $_GET['t']))
{
    $votingdb = $_GET['v'];
    $token = $_GET['t'];

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
        $votings_file = file_get_contents('votings.js');
        $jsonStart = strpos($votings_file, '[');
        $jsonEnd = strrpos($votings_file, ']') + 1;
        $jsonFile = substr($votings_file, $jsonStart, $jsonEnd - $jsonStart);
        $jsonFile = trim($jsonFile);
        $jsonFile = preg_replace('/(\w+):/', '"$1":', $jsonFile);
        $votings = json_decode($jsonFile, true);
        if(json_last_error() !== JSON_ERROR_NONE)
        {
            die('Error decoding JSON: '.json_last_error_msg());
        }

        $exists = false;
        foreach($votings as $voting)
        {
            if($voting['voting_name'] === $votingdb)
            {
                $exists = true;
                break;
            }
        }

        if($exists)
        {
            $sql = "SELECT * FROM $votingdb WHERE token = ?";
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
                $sql = "DELETE FROM $votingdb WHERE token = ?";
                $stmt = mysqli_stmt_init($conn);
                if (!mysqli_stmt_prepare($stmt, $sql))
                {
                    die(mysqli_error($conn));
                }

                mysqli_stmt_bind_param($stmt, "s", $token);
                mysqli_stmt_execute($stmt);

                echo "Głos został usunięty";
            }
            else
            {
                echo "Błędny link lub głos został już usunięty";
            }
        }
        else
        {
            echo "Błędny link";
        }
    }
}


?>