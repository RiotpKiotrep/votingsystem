<?php
// helper file for creating the keys. Currently stored in main website directory - in final, to be moved to separate directory
die("Already generated");

require 'vendor/autoload.php';
use phpseclib3\Crypt\RSA;

$private = RSA::createKey(2048);
$public = $private->getPublicKey();

file_put_contents('private.pem', $private->toString('PKCS1'));
file_put_contents('public.pem', $public->toString('PKCS1'));

echo "Keys generated and saved.";