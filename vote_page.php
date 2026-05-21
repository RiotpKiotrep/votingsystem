<?php
session_start();

require_once 'functions.php';

$user_data = check_login($conn);

$email = $user_data['email'];
$log = "User $email has chosen a voting";
logger($log);

$id = $_GET['id'] ?? null;
if(!$id || !ctype_digit($id))
{
    header("Refresh:5; url=index.php");
    die("Wrong voting ID");
}

$voting = getVotingConfigById($id);
if(!$voting)
{
    header("Refresh:5; url=index.php");
    die("Voting doesn't exist");
}

$expiry_date = new DateTime($voting['expiry_date']);
$now = new DateTime();

if($now >= $expiry_date || $voting['voting_ended'] === true)
{
    header("Refresh:5; url=index.php");
    die("Voting has expired or ended.");
}


$pdo = getDB('voting_system_db');

$hashed_email = hash('sha256',$email);
$table = $voting['voting_name'];

$stmt = $pdo->prepare("SELECT 1 FROM permitted_users WHERE email = ? AND voting = ?");
$stmt->execute([$hashed_email, $table]);
if(!$stmt->fetch()) 
{
    logger("Unauthorized access attempt by $email");
    header("Refresh:5; url=index.php");
    die("Not allowed to vote.");
}

$stmt = $pdo->prepare("SELECT 1 FROM `$table` WHERE email = ?");
$stmt->execute([$hashed_email]);
if($stmt->fetch()) {
    logger("User $email tried voting twice");
    header("Refresh:5; url=index.php");
    die("Already voted.");
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
    
    <form id="voteForm">
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
        fetch('votings.json').then(res => res.json()).then(votings => {
            const id = new URLSearchParams(window.location.search).get('id');
            const voting = votings.find(v => v.id == id);
        
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

            var email = document.createElement("input")
            email.type = "hidden";
            email.name = "email";
            email.value = "<?php echo htmlspecialchars($user_data['email']); ?>";
            candidates.insertAdjacentElement('beforeend', email);
        });

        document.getElementById('voteForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const selected = document.querySelector('input[name="candidate"]:checked');
            if (!selected) { alert('Please choose a candidate'); return; }
            const vote = selected.value;

            try {
                let blindedVote = blind(vote); 

                const authResponse = await fetch('authorize_vote.php', {
                    method: 'POST',
                    body: new URLSearchParams({ blinded_vote: blindedVote })
                });
                const blindedSignature = await authResponse.text();

                let realSignature = unblind(blindedSignature);

                const submitResponse = await fetch('send_vote.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        candidate: vote,
                        signature: realSignature,
                        votingdb: document.querySelector('input[name="votingdb"]').value,
                        email: document.querySelector('input[name="email"]').value
                    })
                });

                const result = await submitResponse.text();
                alert(result);
                window.location.href = 'index.php';

            } catch (err) {
                console.error("Voting failed:", err);
                alert("An error occurred during secure submission.");
            }
        });
    </script>
</body>
</html>