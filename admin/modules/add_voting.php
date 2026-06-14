<?php
session_start();
require_once '../../functions.php';
check_admin_login();

?>

<h2>Add new voting</h2>

<form id="add_voting_form" onsubmit="event.preventDefault(); submitVotingAction('add_voting_form');">
    <input type="hidden" name="action" value="add">

    <label for="voting_name">Voting name (letters, numbers, underscores only):</label>
    <input type="text" id="voting_name" name="voting_name" required>

    <label for="title">Visible title:</label>
    <input type="text" id="title" name="title" required>

    <label for="description">Description:</label>
    <input type="text" id="description" name="description" required>

    <label for="candidates">Candidates (comma-separated):</label>
    <input type="text" id="candidates" name="candidates" required>

    <label for="expiry_date">Expiry date:</label>
    <input type="datetime-local" id="expiry_date" name="expiry_date" required>

    <button type="submit">Add voting</button>
</form>
