<?php
// api/db.php
// This file connects to your XAMPP MySQL database.

// --- DATABASE CONFIGURATION ---
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
// This is blank by default on XAMPP.
define('DB_PASSWORD', ''); 
define('DB_NAME', 'student_mgmt');

// --- ESTABLISH CONNECTION ---
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check for connection errors.
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set the header so all responses from this API are in JSON format.
header('Content-Type: application/json');
?>
