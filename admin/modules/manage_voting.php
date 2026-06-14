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

$isSuperAdmin = ($_SESSION['admin_role'] === 'SUPERADMIN');

?>
<h2>Manage voting: <?= htmlspecialchars($voting['title']) ?></h2>

<p><strong>Current end date:</strong> <?= htmlspecialchars($voting['expiry_date']) ?></p>
<p><strong>Status:</strong> 
    <?= $voting['voting_ended'] ? "<span style='color:orange;'>Ended</span>" : "<span style='color:green;'>Active</span>" ?>
</p>

<hr>

<!-- END VOTING -->
<h3>End voting</h3>

<?php if (!$voting['voting_ended']): ?>
    <form id="endVotingForm">
        <input type="hidden" name="id" value="<?= $votingId ?>">
        <input type="hidden" name="action" value="end">
        <button type="button" onclick="submitVotingAction('endVotingForm')">End voting</button>
    </form>
<?php else: ?>
    <p>This voting has already been ended.</p>
<?php endif; ?>

<hr>

<!-- RESUME VOTING -->
<h3>Resume voting</h3>

<?php if ($voting['voting_ended']): ?>

    <?php if ($isSuperAdmin): ?>
        <form id="resumeVotingForm">
            <input type="hidden" name="id" value="<?= $votingId ?>">
            <input type="hidden" name="action" value="resume">
            <button type="button" onclick="submitVotingAction('resumeVotingForm')">Resume voting</button>
        </form>
    <?php else: ?>
        <button disabled>Resume voting (Superadmin only)</button>

        <!-- Placeholder for future "request resume" feature -->
        <p style="font-size: 0.9em; color: #555;">
            Request resume feature coming soon.
        </p>
    <?php endif; ?>

<?php else: ?>
    <p>Voting is active — no need to resume.</p>
<?php endif; ?>

<hr>

<!-- CHANGE END DATE -->
<h3>Change voting end date</h3>

<form id="changeDateForm">
    <input type="hidden" name="id" value="<?= $votingId ?>">
    <input type="hidden" name="action" value="change_date">

    <label for="new_date">New end date:</label>
    <input type="datetime-local" name="new_date" id="new_date" required>

    <button type="button" onclick="submitVotingAction('changeDateForm')">Update end date</button>
</form>

<hr>