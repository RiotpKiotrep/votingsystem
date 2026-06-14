<?php
session_start();
require_once '../../functions.php';
check_admin_login();

$votingId = $_GET['id'] ?? null;
if (!$votingId) {
    echo "<p>No voting selected.</p>";
    exit;
}

$voting = getVotingConfigById($votingId);
if (!$voting) {
    echo "<p>Invalid voting ID.</p>";
    exit;
}

if ($voting['voting_ended']) {
    echo "<h2>Add users</h2>";
    echo "<p>This voting has already ended. You cannot add users.</p>";
    exit;
}
?>

<h2>Add users to: <?= htmlspecialchars($voting['title']) ?></h2>

<p>Enter email addresses of users who should be allowed to vote.</p>

<label for="email_input">Email:</label>
<input type="text" id="email_input" placeholder="user@example.com">

<button type="button" onclick="addEmail()">Add email</button>

<div id="email_list" style="margin-top: 10px;"></div>

<form id="addUsersForm">
    <input type="hidden" name="id" value="<?= $votingId ?>">
    <input type="hidden" name="action" value="add_users">
    <input type="hidden" id="hidden_emails" name="emails">
    <button type="button" onclick="submitVotingAction('addUsersForm')">Save users</button>
</form>

<hr>

<p><strong>Note:</strong> Users added here will be able to vote immediately.</p>
