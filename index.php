<?php
session_start();

include("connection.php");
include("functions.php");

$user_data = check_login($conn);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Main page</title>
</head>
<body>
    <div class="header">
        <h1>Strona główna</h1>
        <hr>
    </div>
    
    <div id="welcome"> Witaj, <?php echo $user_data['first_name']; ?>!</div>
    <a href="logout.php">Wyloguj się</a>

    <div class="main">
        <div class="intro">
            <h3>Wybierz głosowanie, w którym chcesz uczestniczyć:</h2>
        </div>
        <div class="list">
            <ol>
                <!-- votes list -->
            </ol>
        </div>
    </div>
    <script src="votings.js"></script>
    <script>
        console.log(votings);
        var container = document.querySelector('ol');
        for(let voting of votings)
        {
            var html = `
                <li class="row">
                    <a href="/votingsystem/vote_page.php?${voting.id}">
                        <h4 class="title">
                            ${voting.title}
                        </h4>
                </li>
            `
            container.insertAdjacentHTML('beforeend', html);
        }
    </script>
</body>
</html>