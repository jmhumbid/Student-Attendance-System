<?php
session_start();

// Log start of the script
error_log('record_attendance.php: Script started.');

// Ensure it's an AJAX POST request and the instructor is logged in
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['instructor_logged_in']) || $_SESSION['instructor_logged_in'] !== true) {
    http_response_code(403); // Forbidden
    error_log('record_attendance.php: Invalid request or not logged in.');
    echo json_encode(['success' => false, 'message' => 'Invalid request or not logged in.']);
    exit();
}

require_once 'db.php'; // Ensure db.php is included

error_log('record_attendance.php: db.php included.');

$class_id = $_POST['class_id'] ?? null;
$scanned_student_id = $_POST['student_id'] ?? null; // This is the student's ID string from the QR code

error_log('record_attendance.php: Received class_id: ' . ($class_id ?? 'null') . ', student_id: ' . ($scanned_student_id ?? 'null'));

$response = ['success' => false, 'message' => 'An error occurred.'];

if ($class_id && $scanned_student_id) {
    // Find the internal student ID based on the scanned student_id string
    error_log('record_attendance.php: Attempting to find internal student ID for: ' . $scanned_student_id);
    $stmt = $conn->prepare('SELECT id, name FROM students WHERE student_id = ?');
    if ($stmt === false) {
        error_log('record_attendance.php: Prepare failed - ' . $conn->error);
        $response['message'] = 'Database error preparing student lookup.';
    } else {
        $stmt->bind_param('s', $scanned_student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();

        if ($student) {
            $internal_student_id = $student['id'];
            error_log('record_attendance.php: Found student with internal ID: ' . $internal_student_id);

            // Check if the class belongs to the logged-in instructor (security check)
             $instructor_id = $_SESSION['instructor_id'] ?? null;
             error_log('record_attendance.php: Checking class ownership for class_id: ' . $class_id . ' and instructor_id: ' . ($instructor_id ?? 'null'));
             $stmt = $conn->prepare('SELECT id FROM classes WHERE id = ? AND instructor_id = ?');
             if ($stmt === false) {
                 error_log('record_attendance.php: Prepare failed (class check) - ' . $conn->error);
                 $response['message'] = 'Database error preparing class check.';
             } else {
                 $stmt->bind_param('ii', $class_id, $instructor_id);
                 $stmt->execute();
                 $owned_class = $stmt->fetch();
                 $stmt->close();

                 if ($owned_class) {
                    error_log('record_attendance.php: Class ownership confirmed. Checking student enrollment.');
                    // Check if student is enrolled in this class
                    $enrollment_stmt = $conn->prepare('SELECT id FROM class_students WHERE class_id = ? AND student_id = ?');
                     if ($enrollment_stmt === false) {
                         error_log('record_attendance.php: Prepare failed (enrollment check) - ' . $conn->error);
                         $response['message'] = 'Database error preparing enrollment check.';
                     } else {
                         $enrollment_stmt->bind_param('ii', $class_id, $internal_student_id);
                         $enrollment_stmt->execute();
                         $is_enrolled = $enrollment_stmt->fetch();
                         $enrollment_stmt->close();

                         if ($is_enrolled) {
                            error_log('record_attendance.php: Student is enrolled. Attempting to record attendance.');
                            // Get current time using PHP (respects date_default_timezone_set)
                            $current_time = date('Y-m-d H:i:s');

                            error_log('record_attendance.php: Attempting to record attendance for class_id: ' . $class_id . ', student_id: ' . $internal_student_id . ' with timestamp: ' . $current_time);

                            $stmt = $conn->prepare('INSERT INTO attendance (class_id, student_id, timestamp) VALUES (?, ?, ?)');
                            if ($stmt === false) {
                                error_log('record_attendance.php: Prepare failed - ' . $conn->error);
                                $response['message'] = 'Database error preparing attendance record.';
                            } else {
                                $stmt->bind_param('iss', $class_id, $internal_student_id, $current_time);
                                $stmt->execute();
                                $stmt->close();

                                error_log('record_attendance.php: Attendance recorded successfully.');
                                $response = ['success' => true, 'message' => 'Attendance recorded.', 'student_name' => $student['name']];
                            }
                         } else {
                             error_log('record_attendance.php: Student is not enrolled in class_id: ' . $class_id . ' student_id: ' . $internal_student_id);
                             $response = ['success' => false, 'message' => 'Student is not enrolled in this class.'];
                         }
                     }
                 } else {
                     error_log('record_attendance.php: Permission denied for class_id: ' . $class_id);
                     $response = ['success' => false, 'message' => 'You do not have permission to record attendance for this class.'];
                 }
             }

        } else {
            error_log('record_attendance.php: Invalid student ID received: ' . $scanned_student_id);
            $response = ['success' => false, 'message' => 'Invalid student ID.'];
        }
    }
} else {
    error_log('record_attendance.php: Missing class ID or student ID in POST data.');
    $response = ['success' => false, 'message' => 'Missing class ID or student ID.'];
}

header('Content-Type: application/json');
echo json_encode($response);

// Log end of the script
error_log('record_attendance.php: Script finished.');

$conn->close(); // Close database connection
?> 