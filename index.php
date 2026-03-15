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
        <h1>Voting system - main page</h1>
        <hr>
    </div>
    
    <div id="welcome" class="welcome"> Hello, <?php echo $user_data['first_name']; ?>!<br>
    <a href="logout.php">Log out</a>
    </div>

    <div class="main">
        <div class="intro">
            <h3>Choose voting to participate in:</h2>
        </div>
        <div class="list">
            <ol>
                <!-- votes list -->
            </ol>
        </div>
    </div>
    <script>
        fetch('votings.json').then(function(response)
        {
            return response.json();
        }).then(function(votings)
        {
            var container = document.querySelector('ol');
            
            var now = new Date();

            for (let voting of votings) {
                let expiry = new Date(voting.expiry_date);
                let isExpired = now >= expiry;

                let statusLabel = "";
                let cssClass = "";

                if (isExpired) {
                    statusLabel = "<span class='expired-label'>Expired</span>";
                    cssClass = "expired";
                } else if (voting.voting_ended) {
                    statusLabel = "<span class='ended-label'>Ended</span>";
                    cssClass = "ended";
                } else {
                    statusLabel = "<span class='active-label'>Active</span>";
                    cssClass = "active";
                }

                var html = `
                    <li class="row ${cssClass}">
                        <a href="/votingsystem/vote_page.php?${voting.id}">
                            <h4 class="title">${voting.title}</h4>
                            <div class="expiry">Expires: ${voting.expiry_date}</div>
                            ${statusLabel}
                        </a>
                    </li>
                `;

                container.insertAdjacentHTML('beforeend', html);
            }
        })
    </script>
</body>
</html>