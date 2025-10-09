<?php
// api/generate_hash.php
// This is a temporary tool to create a secure password hash.

// The password we want to use for the 'john' user.
$password = 'admin123';

// Use PHP's built-in function to create a secure hash.
// This is the SAME method that login.php uses to verify.
$hash = password_hash($password, PASSWORD_DEFAULT);

// Display the hash on the screen.
echo $hash;

?>