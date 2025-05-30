<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin_login.php');
    exit();
}
require_once 'db.php';
$students = get_students();
// Handle delete
if (isset($_GET['delete'])) {
    $del_id = $_GET['delete'];
    $conn->query("DELETE FROM students WHERE student_id='" . $conn->real_escape_string($del_id) . "'");
    header('Location: admin_students.php');
    exit();
}
// Handle edit
$edit_student = null;
if (isset($_GET['edit'])) {
    foreach ($students as $s) {
        if ($s['student_id'] === $_GET['edit']) {
            $edit_student = $s;
            break;
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_student_id'])) {
    $student_id = $_POST['edit_student_id'];
    $name = trim($_POST['name'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $conn->query("UPDATE students SET name='" . $conn->real_escape_string($name) . "', gender='" . $conn->real_escape_string($gender) . "', year='" . $conn->real_escape_string($year) . "' WHERE student_id='" . $conn->real_escape_string($student_id) . "'");
    header('Location: admin_students.php');
    exit();
}
$students = get_students(); // refresh after edit/delete
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Students</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; } .sidebar { min-width: 240px; } .sidebar-link.active, .sidebar-link:hover { background: #e5e7eb; color: #2563eb; }</style>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <!-- Sidebar -->
    <aside class="sidebar bg-white shadow-lg flex flex-col p-6">
        <div class="flex flex-col items-center mb-10">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Admin Icon" class="w-16 h-16 rounded-full mb-2">
            <span class="text-xl font-bold text-gray-800">Administrator</span>
        </div>
        <nav class="flex flex-col gap-2">
            <a href="admin_dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg font-semibold">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="admin_students.php" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg font-semibold active">
                <i class="fas fa-users"></i> Registered Students
            </a>
            <a href="admin_instructors.php" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg font-semibold">
                <i class="fas fa-chalkboard-teacher"></i> Registered Instructors
            </a>
            <a href="register_instructor.php" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg font-semibold">
                <i class="fas fa-user-plus"></i> Register Instructor
            </a>
            <a href="admin_logout.php" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg font-semibold text-red-600 mt-8">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </aside>
    <!-- Main Content -->
    <main class="flex-1 p-8">
        <div class="w-full max-w-5xl mx-auto bg-white rounded-2xl shadow-2xl p-8 mt-8">
            <div class="flex flex-col items-center mb-10">
                <h1 class="text-2xl font-bold text-blue-700 flex items-center gap-2"><i class="fas fa-users"></i> Registered Students</h1>
            </div>
            <div class="overflow-x-auto">
            <table class="min-w-full bg-white border rounded-lg">
                <thead class="bg-blue-100">
                    <tr>
                        <th class="py-2 px-4 border-b">Student ID</th>
                        <th class="py-2 px-4 border-b">Name</th>
                        <th class="py-2 px-4 border-b">Gender</th>
                        <th class="py-2 px-4 border-b">Year</th>
                        <th class="py-2 px-4 border-b">Course</th>
                        <th class="py-2 px-4 border-b">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($students as $s): ?>
                    <tr class="hover:bg-blue-50 transition">
                        <td class="py-2 px-4 border-b"><?= htmlspecialchars($s['student_id']) ?></td>
                        <td class="py-2 px-4 border-b">
                            <?php if ($edit_student && $edit_student['student_id'] === $s['student_id']): ?>
                                <form method="POST" class="flex gap-2 items-center">
                                    <input type="hidden" name="edit_student_id" value="<?= htmlspecialchars($s['student_id']) ?>">
                                    <input type="text" name="name" value="<?= htmlspecialchars($edit_student['name']) ?>" class="border rounded px-2 py-1 w-32">
                            <?php else: ?>
                                <?= htmlspecialchars($s['name']) ?>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-4 border-b">
                            <?php if ($edit_student && $edit_student['student_id'] === $s['student_id']): ?>
                                <select name="gender" class="border rounded px-2 py-1">
                                    <option value="Male" <?= $edit_student['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $edit_student['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= $edit_student['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            <?php else: ?>
                                <?= htmlspecialchars($s['gender']) ?>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-4 border-b">
                            <?php if ($edit_student && $edit_student['student_id'] === $s['student_id']): ?>
                                <select name="year" class="border rounded px-2 py-1">
                                    <option value="1st Year" <?= $edit_student['year'] === '1st Year' ? 'selected' : '' ?>>1st Year</option>
                                    <option value="2nd Year" <?= $edit_student['year'] === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                                    <option value="3rd Year" <?= $edit_student['year'] === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                                    <option value="4th Year" <?= $edit_student['year'] === '4th Year' ? 'selected' : '' ?>>4th Year</option>
                                </select>
                            <?php else: ?>
                                <?= htmlspecialchars($s['year']) ?>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-4 border-b"><?= htmlspecialchars($s['course']) ?></td>
                        <td class="py-2 px-4 border-b">
                            <?php if ($edit_student && $edit_student['student_id'] === $s['student_id']): ?>
                                <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 transition"><i class="fas fa-save"></i></button>
                                </form>
                                <a href="admin_students.php" class="ml-2 text-red-500 hover:text-red-700"><i class="fas fa-times"></i></a>
                            <?php else: ?>
                                <a href="admin_students.php?edit=<?= urlencode($s['student_id']) ?>" class="text-blue-500 hover:text-blue-700 mr-2"><i class="fas fa-edit"></i></a>
                                <a href="admin_students.php?delete=<?= urlencode($s['student_id']) ?>" class="text-red-500 hover:text-red-700" onclick="return confirm('Are you sure you want to delete this student?');"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </main>
</body>
</html> 