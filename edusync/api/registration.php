<?php
// api/register.php
// CORRECTED VERSION - This version includes more robust error handling to prevent crashes.

// Show all PHP errors for easier debugging during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

// Set the header to JSON at the very beginning
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

// --- Input Validation ---
$name = $input['name'] ?? '';
$email = $input['email'] ?? '';
$phone = $input['phone'] ?? '';
$dept_id = $input['dept_id'] ?? null;
$year = $input['year'] ?? 1; // Default to 1 if not provided
$semester = $input['semester'] ?? 1; // Default to 1 if not provided
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (empty($name) || empty($email) || empty($username) || empty($password) || empty($dept_id)) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
    exit;
}

// --- Check for Duplicates ---
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already exists.']);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt = $conn->prepare("SELECT student_id FROM students WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email address is already registered.']);
    $stmt->close();
    $conn->close();
    exit;
}

// --- Use a Transaction for Safety ---
$conn->begin_transaction();

try {
    // 1. Create the student record
    $stmt_student = $conn->prepare("INSERT INTO students (name, email, phone, dept_id, year, semester) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt_student === false) { throw new Exception("Failed to prepare student insert statement."); }
    $stmt_student->bind_param("sssiii", $name, $email, $phone, $dept_id, $year, $semester);
    $stmt_student->execute();

    $new_student_id = $conn->insert_id;
    if ($new_student_id === 0) { throw new Exception("Failed to create student record."); }
    $stmt_student->close();

    // 2. Create the user login record
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $student_role_id = 2; // Assuming 'student' role has role_id = 2

    $stmt_user = $conn->prepare("INSERT INTO users (username, password_hash, role_id, linked_student_id) VALUES (?, ?, ?, ?)");
    if ($stmt_user === false) { throw new Exception("Failed to prepare user insert statement."); }
    $stmt_user->bind_param("ssii", $username, $password_hash, $student_role_id, $new_student_id);
    $stmt_user->execute();
    $stmt_user->close();

    // If both queries were successful, commit the transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Registration successful! You can now log in.']);

} catch (Exception $e) {
    // If anything went wrong, roll back the transaction
    $conn->rollback();
    // For debugging, you can log $e->getMessage() to a file
    echo json_encode(['success' => false, 'message' => 'An error occurred during registration. Please contact support.']);
}

$conn->close();
?>

