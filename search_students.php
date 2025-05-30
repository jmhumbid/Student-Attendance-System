<?php
require_once 'db.php';
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare('SELECT student_id, name FROM students WHERE name LIKE ? ORDER BY name LIMIT 10');
$like = '%' . $q . '%';
$stmt->bind_param('s', $like);
$stmt->execute();
$result = $stmt->get_result();
$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = [
        'student_id' => $row['student_id'],
        'name' => $row['name']
    ];
}
$stmt->close();
echo json_encode($students); 