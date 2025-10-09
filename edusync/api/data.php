<?php
// api/data.php
// FINAL, STABLE VERSION
// Built on the user-confirmed working version and safely adds all features with robust error-checking.

require_once 'db.php';
session_start();

// --- SECURITY CHECK ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized status
    echo json_encode(['error' => 'You must be logged in to access data.']);
    exit;
}

$endpoint = $_GET['endpoint'] ?? '';
$role = $_SESSION['role'];
$data = [];

// This is the correct way to get the student ID for the logged-in user.
$student_id = null;
if ($role == 'student' && isset($_SESSION['linked_student_id'])) {
    $student_id = $_SESSION['linked_student_id'];
}

switch ($endpoint) {
    case 'dashboard':
        if ($role == 'student' && $student_id) {
            // Student dashboard logic from the working version
            $stmt = $conn->prepare("SELECT cgpa, attendance FROM academics WHERE student_id = ? ORDER BY semester DESC LIMIT 1");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $academics = $stmt->get_result()->fetch_assoc();

            $stmt = $conn->prepare("SELECT SUM(amount) as pending_fees FROM fees WHERE student_id = ? AND status = 'Pending'");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $fees = $stmt->get_result()->fetch_assoc();

            $stmt = $conn->prepare("SELECT COUNT(*) as issued_count FROM book_issues WHERE student_id = ? AND return_date IS NULL");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $library = $stmt->get_result()->fetch_assoc();

            // --- SAFELY ADDED: Announcements for Student Dashboard ---
            $announcements = [];
            $announcementsResult = $conn->query("SELECT title, content, type, post_date FROM announcements ORDER BY post_date DESC LIMIT 3");
            if ($announcementsResult) {
                while ($row = $announcementsResult->fetch_assoc()) {
                    $announcements[] = $row;
                }
            }

            $data = [
                'cgpa' => $academics['cgpa'] ?? 'N/A',
                'attendance' => $academics['attendance'] ?? 'N/A',
                'pending_fees' => $fees['pending_fees'] ?? 0,
                'books_issued' => $library['issued_count'] ?? 0,
                'announcements' => $announcements // Added announcements
            ];
        } else if ($role == 'admin') {
             // --- SAFELY ADDED: Enhanced Admin Dashboard Stats ---
             $totalStudentsResult = $conn->query("SELECT COUNT(*) as count FROM students");
             $totalStudents = $totalStudentsResult ? $totalStudentsResult->fetch_assoc()['count'] : 0;

             $pendingFeesResult = $conn->query("SELECT SUM(amount) as total FROM fees WHERE status = 'Pending'");
             $pendingFeesRow = $pendingFeesResult ? $pendingFeesResult->fetch_assoc() : null;
             $pendingFees = $pendingFeesRow['total'] ?? 0;

             $departmentsResult = $conn->query("SELECT COUNT(*) as count FROM departments");
             $departments = $departmentsResult ? $departmentsResult->fetch_assoc()['count'] : 0;

             $hostelOccupancyResult = $conn->query("SELECT SUM(capacity) as total_capacity, SUM(occupied) as total_occupied FROM hostel");
             $hostelRow = $hostelOccupancyResult ? $hostelOccupancyResult->fetch_assoc() : null;
             $hostelOccupancy = 0;
             if ($hostelRow && !is_null($hostelRow['total_capacity']) && $hostelRow['total_capacity'] > 0) {
                 $hostelOccupancy = ($hostelRow['total_occupied'] / $hostelRow['total_capacity']) * 100;
             }

             $data = [
                'total_students' => (int) $totalStudents,
                'pending_fees' => (float) $pendingFees,
                'departments' => (int) $departments,
                'hostel_occupancy' => round($hostelOccupancy, 1)
             ];
        }
        break;

    // STUDENT-ONLY ENDPOINTS
    case 'academics': if ($role == 'student' && $student_id) { $stmt = $conn->prepare("SELECT * FROM academics WHERE student_id = ?"); $stmt->bind_param("i", $student_id); $stmt->execute(); $result = $stmt->get_result(); if ($result) { while($row = $result->fetch_assoc()) { $data[] = $row; } } } break;
    case 'fees': if ($role == 'student' && $student_id) { $stmt = $conn->prepare("SELECT * FROM fees WHERE student_id = ?"); $stmt->bind_param("i", $student_id); $stmt->execute(); $result = $stmt->get_result(); if ($result) { while($row = $result->fetch_assoc()) { $data[] = $row; } } } break;
    case 'library': if ($role == 'student' && $student_id) { $stmt = $conn->prepare("SELECT b.title, bi.issue_date, bi.due_date, bi.return_date, bi.fine FROM book_issues bi JOIN library b ON bi.book_id = b.book_id WHERE bi.student_id = ?"); $stmt->bind_param("i", $student_id); $stmt->execute(); $result = $stmt->get_result(); if ($result) { while($row = $result->fetch_assoc()) { $data[] = $row; } } } break;
    case 'hostel': if ($role == 'student' && $student_id) { $stmt = $conn->prepare("SELECT h.room_number FROM hostel_allocations ha JOIN hostel h ON ha.room_id = h.room_id WHERE ha.student_id = ?"); $stmt->bind_param("i", $student_id); $stmt->execute(); $result = $stmt->get_result(); $data = $result ? $result->fetch_assoc() : ['room_number' => null]; if(!$data){$data = ['room_number' => null];} } break;

    // ADMIN-ACCESS ENDPOINTS
    case 'students': if ($role == 'admin') { $result = $conn->query("SELECT s.name, s.email, d.dept_name FROM students s JOIN departments d ON s.dept_id = d.dept_id"); if ($result) { while($row = $result->fetch_assoc()) { $data[] = $row; } } } break;
    case 'fees_all': if ($role == 'admin') { $result = $conn->query("SELECT f.fee_id, s.name as student_name, f.amount, f.status FROM fees f JOIN students s ON f.student_id = s.student_id ORDER BY f.status, s.name"); if ($result) { while($row = $result->fetch_assoc()) { $data[] = $row; } } } break;
    case 'academics_all': if ($role == 'admin') { $result = $conn->query("SELECT a.record_id, s.name as student_name, a.semester, a.cgpa, a.attendance FROM academics a JOIN students s ON a.student_id = s.student_id ORDER BY s.name, a.semester"); if ($result) { while($row = $result->fetch_assoc()) { $data[] = $row; } } } break;
    case 'library_all': if ($role == 'admin') { $result = $conn->query("SELECT bi.issue_id, s.name as student_name, b.title as book_title, bi.issue_date, bi.due_date, bi.fine FROM book_issues bi JOIN students s ON bi.student_id = s.student_id JOIN library b ON bi.book_id = b.book_id WHERE bi.return_date IS NULL ORDER BY bi.due_date"); if ($result) { while($row = $result->fetch_assoc()) { $data[] = $row; } } } break;
    case 'hostel_all': if ($role == 'admin') { $result = $conn->query("SELECT ha.allocation_id, s.name as student_name, h.room_number, d.dept_name FROM hostel_allocations ha JOIN students s ON ha.student_id = s.student_id JOIN hostel h ON ha.room_id = h.room_id JOIN departments d ON s.dept_id = d.dept_id ORDER BY h.room_number"); if ($result) { while($row = $result->fetch_assoc()) { $data[] = $row; } } } break;
}

echo json_encode($data);
$conn->close();
?>

