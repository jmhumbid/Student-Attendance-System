<?php
session_start();

header('Content-Type: application/json');

// Check if instructor is logged in
if (!isset($_SESSION['instructor_logged_in']) || $_SESSION['instructor_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once 'db.php'; // Include database connection

$class_id = $_GET['class_id'] ?? null;
$date_str = $_GET['date'] ?? null;
$start_str = $_GET['start'] ?? null; // New: Get start date for range
$end_str = $_GET['end'] ?? null; // New: Get end date for range

// Validate inputs
if ($class_id === null || ($date_str === null && ($start_str === null || $end_str === null))) {
    echo json_encode(['error' => 'Missing class_id or date/range parameters']);
    exit();
}

// Determine if filtering by single date or a range
$is_range_query = ($start_str !== null && $end_str !== null);

// Validate and format target date(s) using the default timezone
$timezone = new DateTimeZone(date_default_timezone_get());

$target_date_start = null;
$target_date_end = null;

if ($is_range_query) {
    $target_date_start = new DateTime($start_str, $timezone);
    $target_date_end = new DateTime($end_str, $timezone);
} else { // Single date query
    $target_date_start = DateTime::createFromFormat('Y-m-d H:i:s', $date_str . ' 00:00:00', $timezone);
    $target_date_end = DateTime::createFromFormat('Y-m-d H:i:s', $date_str . ' 23:59:59', $timezone);
}


if (!$target_date_start || !$target_date_end) {
     echo json_encode(['error' => 'Invalid date format provided for filtering.']);
     exit();
}


// Get enrolled students for the class
// This function should return student data including 'id' (internal), 'student_id' (string), and 'name'
$enrolled_students = get_enrolled_students($class_id);

$attendance_data = [];

if ($enrolled_students) {
    // Fetch all attendance records for this class within the specified date range
    // We fetch all records for the class first to handle timezone conversion in PHP
    // Modify query to filter by timestamp range
    $stmt = $conn->prepare('SELECT student_id, timestamp FROM attendance WHERE class_id = ? AND timestamp BETWEEN ? AND ?');
     if ($stmt === false) {
        echo json_encode(['error' => 'Database error preparing attendance query: ' . $conn->error]);
        exit();
    }
    
    // Format dates for SQL query (using UTC or a consistent format)
    // It's generally safer to convert DateTime objects to a standard format like UTC for database interaction
    // Or ensure your database connection timezone is set correctly.
    // For simplicity, assuming database can handle ISO 8601 strings from DateTime objects directly.
    $start_timestamp_str = $target_date_start->format('Y-m-d H:i:s');
    $end_timestamp_str = $target_date_end->format('Y-m-d H:i:s');

    $stmt->bind_param('iss', $class_id, $start_timestamp_str, $end_timestamp_str);
    $stmt->execute();
    $result = $stmt->get_result();

    $present_internal_student_attendance = []; // Store internal student ID and timestamp for present students
    
    // Process fetched attendance records
    while ($row = $result->fetch_assoc()) {
         $present_internal_student_attendance[$row['student_id']] = $row['timestamp']; // Store timestamp by internal student ID
    }
    $stmt->close();

    // Now build the final attendance data for all enrolled students
    foreach ($enrolled_students as $student) {
        $internal_student_id = $student['id'];
        
        // Check if the student has an attendance record in the fetched data for the date range
        $attendance_timestamp = $present_internal_student_attendance[$internal_student_id] ?? null;

        $is_present = ($attendance_timestamp !== null);

        $attendance_record = [
            'student_id' => htmlspecialchars($student['student_id']),
            'name' => htmlspecialchars($student['name']),
            'status' => $is_present ? 'Present' : 'Absent'
        ];

        // Add the timestamp ONLY if the student was present within the range
        if ($is_present) {
            // Format timestamp as ISO 8601 for better JavaScript compatibility
            $attendance_datetime = new DateTime($attendance_timestamp, $timezone);
            $attendance_record['attendance_time'] = $attendance_datetime->format('Y-m-d\TH:i:s');
        }

        // For range queries (week/day view), include all students (present or absent) with their attendance time if present
        // For single date queries, the previous logic already filters by date and includes time for present students.
        // This structure works for both cases, ensuring attendance_time is present when available.

        $attendance_data[] = $attendance_record;
    }
}

echo json_encode(['success' => true, 'attendance' => $attendance_data]);

// Note: get_enrolled_students function in db.php should return 'id', 'student_id' and 'name'.

?> 