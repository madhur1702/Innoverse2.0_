<?php
// api/announcements.php
// Handles creating and deleting announcements for admins.

require_once 'db.php';
session_start();

// Security Check: Only admins can perform these actions.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'You do not have permission to perform this action.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // --- CREATE a new announcement ---
    $input = json_decode(file_get_contents('php://input'), true);
    $title = $input['title'] ?? '';
    $content = $input['content'] ?? '';
    $type = $input['type'] ?? 'General';

    if (empty($title) || empty($content)) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Title and content cannot be empty.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO announcements (title, content, type) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $content, $type);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Announcement created successfully.']);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Failed to create announcement.']);
    }
    $stmt->close();

} elseif ($method === 'DELETE') {
    // --- DELETE an announcement ---
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Announcement ID is required.']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM announcements WHERE announcement_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Announcement deleted successfully.']);
        } else {
            http_response_code(404); // Not Found
            echo json_encode(['success' => false, 'message' => 'Announcement not found.']);
        }
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Failed to delete announcement.']);
    }
    $stmt->close();
}

$conn->close();
?>
