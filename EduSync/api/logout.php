<?php
// api/logout.php
// Handles logging the user out.

session_start();
$_SESSION = array(); // Clear all session variables.
session_destroy(); // Destroy the session.

echo json_encode(['success' => true]);
?>
