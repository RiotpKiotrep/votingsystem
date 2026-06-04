<?php
session_start();

require_once 'functions.php';

$user_data = check_login();

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

$stmt = $pdo->prepare("SELECT 1 FROM permitted_users WHERE email = ? AND voting = ? AND voted = 1");
$stmt->execute([$hashed_email, $table]);
if($stmt->fetch()) {
    logger("User $email tried voting twice");
    header("Refresh:5; url=index.php");
    die("Already voted.");
}

$pub = openssl_pkey_get_public(file_get_contents("public.pem"));
$details = openssl_pkey_get_details($pub);

$n_hex = bin2hex($details['rsa']['n']);
$n_hex = strtolower($n_hex);

$e_raw = $details['rsa']['e'];
$e_int = gmp_intval(gmp_import($e_raw));

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script>
        const RSA_N = <?php echo json_encode($n_hex); ?>;
        const RSA_E = <?php echo json_encode($e_int); ?>;

        // blinding math functions
        function modPow(base, exp, mod) {
            base = base % mod;
            let result = 1n;
            while (exp > 0) {
                if (exp & 1n) result = (result * base) % mod;
                base = (base * base) % mod;
                exp >>= 1n;
            }
            return result;
        }

        function modInverse(a, m) {
            let m0 = m, x0 = 0n, x1 = 1n;
            while (a > 1n) {
                let q = a / m;
                [a, m] = [m, a % m];
                [x0, x1] = [x1 - q * x0, x0];
            }
            return (x1 + m0) % m0;
        }

        async function sha256hex(str) {
            const buf = await crypto.subtle.digest("SHA-256", new TextEncoder().encode(str));
            return Array.from(new Uint8Array(buf)).map(x => x.toString(16).padStart(2, "0")).join("");
        }

        async function blind(message) {
            const hashHex = await sha256hex(message);
            const m = BigInt("0x" + hashHex);
            const n = BigInt("0x" + RSA_N);
            const e = BigInt(RSA_E);

            let r;
            do {
                r = BigInt("0x" + crypto.getRandomValues(new Uint8Array(32))
                    .reduce((a,b)=>a+b.toString(16).padStart(2,"0"),""));
            } while (r % n === 0n);

            window._blind_r = r;

            const blinded = (m * modPow(r, e, n)) % n;
            return blinded.toString();
        }

        function unblind(signedBlinded) {
            const n = BigInt("0x" + RSA_N);
            const s = BigInt(signedBlinded);
            const rInv = modInverse(window._blind_r, n);
            const unblinded = (s * rInv) % n;
            return unblinded.toString();
        }
    </script>
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
            
            // Get the voting name from the hidden input field
            const votingdbValue = document.querySelector('input[name="votingdb"]').value;
            const emailValue = document.querySelector('input[name="email"]').value;

            try {
                let blindedVote = await blind(vote); 

                // 1. Request Authorization (Blind Signature)
                // We must send votingdb so PHP can check permissions
                const authResponse = await fetch('authorize_vote.php', {
                    method: 'POST',
                    body: new URLSearchParams({ 
                        blinded_vote: blindedVote,
                        votingdb: votingdbValue 
                    })
                });
                
                const authResult = await authResponse.text();

                // Check if the server returned an error message instead of a numeric signature
                if (isNaN(authResult) || authResult.trim() === "") {
                    alert("Authorization failed: " + authResult);
                    return;
                }

                // 2. Unblind the signature locally
                let realSignature = unblind(authResult);

                // 3. Submit the actual vote anonymously
                const submitResponse = await fetch('send_vote.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        candidate: vote,
                        signature: realSignature,
                        votingdb: votingdbValue,
                        email: emailValue
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