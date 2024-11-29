<?php
session_start();

include("connection.php");
include("functions.php");

$user_data = check_login($conn);

$email = $user_data['email'];
$log = "User $email has chosen a voting";
logger($log);

$id = $_SERVER['QUERY_STRING'] ?? null;
if(!$id || !ctype_digit($id))
{
    die("Wrong voting ID");
}

$votings_file = file_get_contents('votings.json');
$votings = json_decode($votings_file, true);
if(json_last_error() !== JSON_ERROR_NONE)
{
    die('Error decoding JSON: '.json_last_error_msg());
}
$votingdb = null;
foreach ($votings as $voting) {
    if ((int)$voting['id'] === (int)$id) {
        $votingdb = $voting['voting_name'];
        break;
    }
}

if (!$votingdb) {
    die("Wrong voting ID");
}

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
    $hashed_email = sha1($email);
    //$query = "select * from available_users where email = '$hashed_email' and voting = '$votingdb'";
    $query = "select * from available_users where email = '$email' and voting = '$votingdb'";
    $result = mysqli_query($conn, $query);
    if($result)
    {
        if($result && mysqli_num_rows($result) == 0)
        {
            $log = "User not allowed to vote";
            logger($log);
            
            header("Refresh:5; url=index.php");

            echo "Not allowed to vote";

            die;
        }
    }
    $query = "select * from $votingdb where email = '$hashed_email'";
    $result = mysqli_query($conn, $query);
    if($result)
    {
        if($result && mysqli_num_rows($result) > 0)
        {
            $log = "User has already voted";
            logger($log);

            header("Refresh:5; url=index.php");
            
            echo "Already voted";

            die;
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class="header"></div>
    
    <form action="send_vote.php" method="post">
        <fieldset>
            <!--
            <fieldset>
                <legend>Email (temporary)</legend>
                <input type="text" name="email">
            </fieldset>
            -->
            <br>
            <div class="candidates">
                <!-- candidates list -->
            </div>
            
            <input type="submit" value="Submit">
        </fieldset>
    </form>
    <script>
        fetch('votings.json').then(function(response)
        {
            return response.json();
        }).then(function(votings)
        {
            var id = window.location.search.slice(1);
            var voting = votings.find(v => v.id == id);
        
            var header = document.querySelector('.header');
            var headerHtml = `
                <h2 class = "title">
                    ${voting.title}
                </h2>
                <p class = "description">
                    ${voting.description}
                </p>
            `
            header.insertAdjacentHTML('beforeend', headerHtml);
            
            var candidates = document.querySelector('.candidates');
            
            for(var i=0; i<voting.candidates.length; i++)
            {
                console.log(voting.candidates[i]);
                var radioBtn = document.createElement("input");
                radioBtn.type = "radio";
                radioBtn.id = i+1;
                radioBtn.name = "candidate";
                radioBtn.value = voting.candidates[i];
                radioBtn.required = true;
                candidates.insertAdjacentElement('beforeend', radioBtn);

                var label = document.createElement("label");
                label.htmlFor = i+1;
                label.innerHTML = voting.candidates[i];
                candidates.insertAdjacentElement('beforeend', label);

                candidates.insertAdjacentElement('beforeend', document.createElement("br"));
            }
            
            var db = document.createElement("input")
            db.type = "hidden";
            db.name = "votingdb";
            db.value = voting.voting_name;
            candidates.insertAdjacentElement('beforeend', db);
            
            /* ////// hidden email/UID value to pass for identity check
            var uid = document.createElement("input")
            uid.type = "hidden";
            uid.name = "uid";
            uid.value = 
            candidates.insertAdjacentElement('beforeend', uid);
            */
            var email = document.createElement("input")
            email.type = "hidden";
            email.name = "email";
            email.value = "<?php echo $user_data['email']; ?>";
            candidates.insertAdjacentElement('beforeend', email)
        });

        
    </script>
</body>
</html>