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

$votingdb = $voting['voting_name'] ?? null;
if (!$votingdb) {
    echo "<p>Voting configuration error.</p>";
    exit;
}

$keys = require('../../keys.php');
$pdo = getDB();

// Check if voting ended
$now = new DateTime();
$expiry = new DateTime($voting['expiry_date']);

if (!$voting['voting_ended'] && $now < $expiry) {
    echo "<h2>Count votes</h2>";
    echo "<p>This voting has not ended yet.</p>";
    exit;
}

// Log access
logger("Displayed vote count for: $votingdb");

// Prepare counters
$count = [];
$total_count = 0;
$error_count = 0;

// RSA public key for signature verification
$pub_content = file_get_contents('../../public.pem');
$pub_info = openssl_pkey_get_details(openssl_pkey_get_public($pub_content));
$n = gmp_init(bin2hex($pub_info['rsa']['n']), 16);
$e = gmp_init(bin2hex($pub_info['rsa']['e']), 16);

// Sodium keypair for decrypting sealed votes
$server_secret = $keys['server_secret'];
$server_public = sodium_crypto_box_publickey_from_secretkey($server_secret);
$server_keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey(
    $server_secret,
    $server_public
);

// Fetch encrypted votes
$stmt = $pdo->prepare("SELECT signature, candidate FROM `$votingdb`");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process each vote
foreach ($rows as $row) {
    try {
        // Decrypt candidate
        $hex = $row['candidate'];
        if (!ctype_xdigit($hex)) throw new Exception("Invalid hex");

        $encrypted = hex2bin($hex);
        if (!$encrypted) throw new Exception("hex2bin failed");

        $decrypted = sodium_crypto_box_seal_open($encrypted, $server_keypair);
        if (!$decrypted) throw new Exception("Decryption failed");

        // Extract candidate + referer
        $parts = explode("|", $decrypted);
        if (count($parts) < 2) throw new Exception("Malformed data");

        $candidate_name = $parts[0];
        $referer = $parts[1];

        // Verify RSA blind signature
        $sig = gmp_init($row['signature'], 10);
        $expected_hash = gmp_init(hash("sha256", $candidate_name), 16);
        $actual_hash = gmp_powm($sig, $e, $n);

        if (gmp_cmp($actual_hash, $expected_hash) !== 0) {
            $error_count++;
            logger("Signature mismatch in $votingdb");
            continue;
        }

        // Verify referer matches this voting
        if (strpos($referer, "vote_page.php?id=" . $votingId) !== false) {
            $count[$candidate_name] = ($count[$candidate_name] ?? 0) + 1;
            $total_count++;
        } else {
            $error_count++;
            logger("Referer mismatch: " . $referer);
        }

    } catch (Exception $ex) {
        $error_count++;
        logger("Error processing vote: " . $ex->getMessage());
    }
}

// Fetch number of allowed voters
$stmt = $pdo->prepare("SELECT COUNT(email) AS email_count FROM permitted_users WHERE voting = ?");
$stmt->execute([$votingdb]);
$allowed = $stmt->fetch(PDO::FETCH_ASSOC);
$allowed_count = $allowed['email_count'] ?? 0;

// Output results
echo "<h2>Vote count for: " . htmlspecialchars($voting['title']) . "</h2>";

echo "<h3>Results:</h3>";
if (empty($count)) {
    echo "<p>No valid votes found.</p>";
} else {
    echo "<ul>";
    foreach ($count as $candidate => $num) {
        echo "<li><strong>" . htmlspecialchars($candidate) . "</strong>: $num</li>";
    }
    echo "</ul>";
}

echo "<p><strong>Total counted votes:</strong> $total_count</p>";
echo "<p><strong>Allowed voters:</strong> $allowed_count</p>";

if ($total_count < $allowed_count) {
    $missing = $allowed_count - $total_count;
    echo "<p><strong>$missing users did not vote.</strong><br>Recommendation: contact voting participants.</p>";
}

echo "<p><strong>Errors:</strong> $error_count</p>";
if ($error_count > 0) {
    echo "<p>Recommendation: check database entries.</p>";
}

?>
