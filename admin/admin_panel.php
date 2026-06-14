<?php
session_start();
require_once '../functions.php';
check_admin_login();

$votings = json_decode(file_get_contents('../votings.json'), true);

$selectedVotingId = $_GET['id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin panel</title>
    <link rel="stylesheet" href="../style.css">

    <style>
        .layout {
            display: flex;
            height: calc(100vh - 60px);
        }

        .sidebar {
            width: 260px;
            background-color: lightsteelblue;
            padding: 20px;
            box-sizing: border-box;
            border-right: 2px solid #aaa;
        }

        .content {
            flex-grow: 1;
            padding: 20px;
            box-sizing: border-box;
        }

        .sidebar h2 {
            text-align: center;
            margin-top: 0;
        }

        .sidebar button {
            width: 100%;
        }

        #module_container {
            background: #f5faff;
            padding: 20px;
            border-radius: 8px;
            min-height: 200px;
            border: 1px solid #ccc;
        }
    </style>

    <script>
        function checkPermissions(votingId, callback) {
            fetch(`check_permissions.php?voting_id=${votingId}`)
                .then(res => res.json())
                .then(data => callback(data));
        }
        
        // update buttons on voting change
        function updateButtons() {
            const votingId = document.getElementById('voting_id').value;
            const buttons = document.querySelectorAll('.feature-btn');

            if (!votingId) {
                buttons.forEach(btn => btn.disabled = true);
                return;
            }

            checkPermissions(votingId, allowedModules => {
                buttons.forEach(btn => {
                    const module = btn.dataset.module;
                    btn.disabled = !allowedModules.includes(module);
                });
            });
        }

        // Email list for add_users module
        let emailList = [];

        function addEmail() {
            const emailInput = document.getElementById('email_input');
            if (!emailInput) return; // module not loaded

            const email = emailInput.value.trim();
            if (!email) {
                alert("Please enter an email");
                return;
            }

            if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                alert("Invalid email format");
                return;
            }

            if (emailList.includes(email)) {
                alert("Email already added");
                return;
            }

            emailList.push(email);
            emailInput.value = "";
            updateEmailList();
        }

        function removeEmail(email) {
            const index = emailList.indexOf(email);
            if (index > -1) {
                emailList.splice(index, 1);
                updateEmailList();
            }
        }

        function updateEmailList() {
            const container = document.getElementById('email_list');
            const hidden = document.getElementById('hidden_emails');

            if (!container || !hidden) return; // module not loaded

            container.innerHTML = "";

            emailList.forEach(email => {
                const div = document.createElement('div');
                div.textContent = email + " ";

                const btn = document.createElement('button');
                btn.textContent = "Remove";
                btn.onclick = () => removeEmail(email);

                div.appendChild(btn);
                container.appendChild(div);
            });

            hidden.value = emailList.join(",");
        }

        // Reset email list when module loads
        function resetEmailList() {
            emailList = [];
        }

        function loadModule(moduleName) {
            const votingId = document.getElementById('voting_id').value;
            if (!votingId) {
                alert("Please select a voting first");
                return;
            }

            fetch(`modules/${moduleName}.php?id=${votingId}&_=${Date.now()}`)
                .then(res => {
                    if (res.redirected) {
                        window.location.href = res.url;
                        return;
                    }
                    return res.text();
                })
                .then(html => {
                    if (!html) return;

                    document.getElementById('module_container').innerHTML = html;

                    if (moduleName === "add_users") {
                        resetEmailList();
                    }
                });
        }

        function submitVotingAction(formId) {
            const form = document.getElementById(formId);
            const formData = new FormData(form);

            fetch('handlers/voting_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(res => {
                if (res.redirected) {
                    window.location.href = res.url;
                    return;
                }
                return res.text();
            })
            .then(response => {
                if (!response) return;
                document.getElementById('module_container').innerHTML = response;
            });
        }

        window.onload = updateButtons;
    </script>
</head>

<body>

<div class="header"><h1>Admin panel</h1></div>

<div class="layout">
    <div class="sidebar">
        <?php if ($_SESSION['admin_role'] !== 'AUDITOR'): ?>
            <h2>Add new voting</h2>
            <button class="feature-btn" data-module="add_voting" disabled onclick="loadModule('add_voting')">Add voting</button>
        <?php endif; ?>

        <h2>Manage voting</h2>

        <!-- voting selector -->
        <label for="voting_id">Choose voting:</label>
        <select id="voting_id" name="id" onchange="updateButtons()">
            <option value="">-- Select voting --</option>
            <?php foreach ($votings as $v): ?>
                <option value="<?= $v['id'] ?>"
                    <?= ($selectedVotingId == $v['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($v['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- management buttons -->
        <?php if ($_SESSION['admin_role'] !== 'AUDITOR'): ?>
            <button class="feature-btn" data-module="add_users" disabled onclick="loadModule('add_users')">Add users</button>
            <button class="feature-btn" data-module="manage_voting" disabled onclick="loadModule('manage_voting')">Manage voting</button>
        <?php endif; ?>
        <button class="feature-btn" data-module="count_votes" disabled onclick="loadModule('count_votes')">Count votes</button>

        <?php if ($_SESSION['admin_role'] === 'SUPERADMIN'): ?>
            <h2>Admin functions</h2>
            <button class="feature-btn" data-module="admin_management" disabled onclick="loadModule('admin_management')">Manage admins</button>
        <?php endif; ?>

        <!-- logout button -->
        <form action="logout.php" method="get">
            <button class="return" type="submit">Logout</button>
        </form>
    </div>

    <!-- main content -->
    <div class="content">
        <div id="module_container">
            <h2>Welcome to the admin panel</h2>
            <p>Select a voting on the left to begin.</p>
        </div>
    </div>

</div>

</body>
</html>
