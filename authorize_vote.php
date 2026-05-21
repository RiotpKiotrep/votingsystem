<?php

require_once 'functions.php';

$blinded_vote = $_POST['blinded_vote'];

[$private_key, $public_key] = GetRSAKeys();

$signature = $private_key->sign($blinded_vote);

echo base64_encode($signature);