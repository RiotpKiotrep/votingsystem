<?php
session_start();

if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true)
{
    header("Location: admin_auth.php");
    die;
}

$inactive_time_limit = 5*60; // 1 * 60 seconds
if(isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $inactive_time_limit)
{
    session_unset();
    session_destroy();
    header("Location: admin_auth.php?m=session_expired");
    die;
}
$_SESSION['last_activity'] = time();

$votings_file = file_get_contents('../votings.json');
$votings = json_decode($votings_file, true);
if(json_last_error() !== JSON_ERROR_NONE)
{
    die('Error decoding JSON: '.json_last_error_msg());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css">
    <title>Add users to voting</title>
    <script>
        const emailList = [];

        function addEmail()
        {
            const emailInput = document.getElementById('email_input');
            const email = emailInput.value.trim();
            if(!email)
            {
                alert('Please enter email');
                return;
            }
            if(!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/))
            {
                alert('Invalid email format');
                return;
            }
            if(emailList.includes(email))
            {
                alert('Email already added');
                return;
            }
            emailList.push(email);
            emailInput.value = '';
            updateEmailList();
        }

        function removeEmail(email)
        {
            const index = emailList.indexOf(email);
            if(index > -1)
            {
                emailList.splice(index, 1);
                updateEmailList();
            }
        }

        function updateEmailList()
        {
            const emailListContainer = document.getElementById('email_list');
            emailListContainer.innerHTML = '';
            emailList.forEach(email => {
                const emailItem = document.createElement('div');
                emailItem.textContent = email;
                const removeButton = document.createElement('button');
                removeButton.textContent = 'Remove';
                removeButton.onclick = () => removeEmail(email);
                emailItem.appendChild(removeButton);
                emailListContainer.appendChild(emailItem);
            });
            document.getElementById('hidden_emails').value = emailList.join(',');
        }
    </script>
</head>
<body>
    <div class="header"><h1>Add users to voting</h1></div>
    <div class="menu">
        <form action="add_users_handler.php" method="post">
            <label for="voting_id">Choose voting:</label>
            <select id="voting_id" name="id" required>
                <?php foreach($votings as $voting): if(!$voting['voting_ended']):?>
                    <option value="<?php echo $voting['id'];?>">
                        <?php echo $voting['title'];?>
                    </option>
                <?php endif; endforeach;?>
            </select>
            
            <label for="email_input">Enter email:</label>
            <input type="text" id="email_input" placeholder="user@example.com">
            <button type="button" onclick="addEmail()">Add email</button>

            <div id='email_list'></div>
            <input type="hidden" id="hidden_emails" name="emails">

            <button type="submit">Add users</button>
        </form>

        <br>
        <button class="return" onclick="document.location.href='admin_panel.php'">Return to admin panel</button>
    </div>
</body>
</html>