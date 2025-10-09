<?php
// api/session.php
// Checks if a user is already logged in when the page loads.

session_start();

if (isset($_SESSION['user_id'])) {
    // The user is already logged in.
    echo json_encode([
        'loggedIn' => true,
        'user' => [
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ]
    ]);
} else {
    // The user is not logged in.
    echo json_encode(['loggedIn' => false]);
}
?>
