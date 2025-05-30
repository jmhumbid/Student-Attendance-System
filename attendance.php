<?php
// attendance.php - View attendance records
require_once 'db.php';

$students = get_students();
$attendance = get_attendance_counts();
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css">
    <title>Attendance Records</title>
</head>
<body>
<div class="container">
    <h2>Attendance Records</h2>
    <table border="1" cellpadding="5">
        <tr>
            <th>Student ID</th>
            <th>Name</th>
            <th>Year</th>
            <th>Course</th>
            <th>Attendance Dates</th>
        </tr>
        <?php foreach ($students as $s): ?>
        <tr>
            <td><?= htmlspecialchars($s['student_id']) ?></td>
            <td><?= htmlspecialchars($s['name']) ?></td>
            <td><?= htmlspecialchars($s['year']) ?></td>
            <td><?= htmlspecialchars($s['course']) ?></td>
            <td>
                <?php if (isset($attendance[$s['student_id']])): ?>
                    <?php foreach ($attendance[$s['student_id']] as $timestamp): ?>
                        <?= date('M d, Y h:i A', strtotime($timestamp)) ?><br>
                    <?php endforeach; ?>
                <?php else: ?>
                    No attendance records
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p><a href="index.php">Back to Register</a></p>
    <p><a href="scan.php">Scan QR for Attendance</a></p>
</div>
</body>
</html>
