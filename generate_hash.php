<?php
// Generate password hash for password
$password = 'password';
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "Password: $password\n";
echo "Hash: $hash\n";
echo "\nVerify test: " . (password_verify($password, $hash) ? 'SUCCESS' : 'FAILED');
