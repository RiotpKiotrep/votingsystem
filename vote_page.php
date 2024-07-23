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
    <script src="votings.js"></script>
    <script>
        var id = window.location.search.slice(1);
        var voting = votings.find(v => v.id == id);
        console.log(voting); //working

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
    </script>
</body>
</html>