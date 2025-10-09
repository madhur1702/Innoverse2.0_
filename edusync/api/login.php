<?php
// api/login.php
// This is the final, correct version of the login script.

require_once 'db.php';
session_start();

$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

// Use a prepared statement to prevent SQL injection.
$stmt = $conn->prepare(
    "SELECT u.user_id, u.username, u.password_hash, r.role_name, u.linked_student_id
     FROM users u
     JOIN roles r ON u.role_id = r.role_id
     WHERE u.username = ?"
);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    // A user was found. Now, verify the password.
    if (password_verify($password, $user['password_hash'])) {
        // The password is correct.
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['linked_student_id'] = $user['linked_student_id'];

        echo json_encode([
            'success' => true,
            'user' => [
                'username' => $user['username'],
                'role' => $user['role_name']
            ]
        ]);
    } else {
        // The password was incorrect.
        echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    }
} else {
    // No user with that username was found.
    echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
}

$stmt->close();
$conn->close();
?>