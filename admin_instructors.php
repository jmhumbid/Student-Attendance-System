<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin_login.php');
    exit();
}

include 'db.php'; 

$instructors = [];
$error = "";

$sql = "SELECT id, full_name, instructor_id, department, email FROM instructors ORDER BY full_name";
$result = $conn->query($sql);

if ($result) {
    while($row = $result->fetch_assoc()) {
        $instructors[] = $row;
    }
} else {
    $error = "Database error: " . $conn->error;
}

// $conn->close(); // Closing connection is not strictly necessary at the end of a script

// Handle AJAX delete with password confirmation
if (isset($_POST['action']) && $_POST['action'] === 'delete_instructor') {
    require_once 'db.php';
    $admin_username = $_SESSION['admin_username'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    $instructor_id = intval($_POST['instructor_id'] ?? 0);
    $response = ['success' => false, 'message' => ''];
    // Debug: log the admin username
    error_log('Admin username in session: ' . $admin_username);
    if (!$admin_username) {
        $response['message'] = 'Admin session error. Please log in again.';
    } else {
        // Fetch admin hashed password
        $stmt = $conn->prepare('SELECT password FROM admin WHERE username = ?');
        $stmt->bind_param('s', $admin_username);
        $stmt->execute();
        $stmt->bind_result($hashed);
        $stmt->fetch();
        $stmt->close();
        if ($hashed && password_verify($admin_password, $hashed)) {
            // Delete instructor
            $del_stmt = $conn->prepare('DELETE FROM instructors WHERE id = ?');
            $del_stmt->bind_param('i', $instructor_id);
            $del_stmt->execute();
            $del_stmt->close();
            // Log the deletion in admin logs
            add_log_entry(NULL, 'admin', 'Deleted Instructor: ' . $instructor_id, $_SERVER['REMOTE_ADDR'] ?? NULL, $_SERVER['HTTP_USER_AGENT'] ?? NULL);
            $response['success'] = true;
        } else {
            $response['message'] = 'Wrong password';
        }
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Instructors - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar { min-width: 240px; }
        .sidebar-link.active, .sidebar-link:hover { background: #e5e7eb; color: #2563eb; }
        .table-container { max-height: 60vh; overflow-y: auto; }
        /* Basic table styles for readability */
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background-color: #f8fafc; font-weight: 600; color: #475569; text-transform: uppercase; font-size: 0.85rem; }
        tr:hover { background-color: #f1f5f9; }
    </style>
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
            <a href="admin_students.php" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg font-semibold">
                <i class="fas fa-users"></i> Registered Students
            </a>
            <a href="admin_instructors.php" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg font-semibold active">
                <i class="fas fa-chalkboard-teacher"></i> Registered Instructors
            </a>
            <a href="register_instructor.php" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg font-semibold">
                <i class="fas fa-user-plus"></i> Register Instructor
            </a>
            <a href="admin_logout.php" id="adminLogoutLink" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg font-semibold text-red-600 mt-8">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </aside>
    <!-- Main Content -->
    <main class="flex-1 p-8">
        <div class="w-full max-w-4xl mx-auto bg-white rounded-2xl shadow-2xl p-8 md:p-12">
            <div class="flex flex-col items-center mb-10">
                <h1 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-2 tracking-tight text-center">Registered Instructors</h1>
                <p class="text-gray-500 text-center">List of all instructors in the system</p>
            </div>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            <?php if (empty($instructors)): ?>
                <div class="text-center text-gray-500 text-lg">No instructors registered yet.</div>
            <?php else: ?>
                <div class="table-container">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th>Instructor ID</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($instructors as $instructor): ?>
                                <tr id="row-<?php echo $instructor['id']; ?>">
                                    <td><?php echo htmlspecialchars($instructor['instructor_id']); ?></td>
                                    <td><?php echo htmlspecialchars($instructor['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($instructor['department']); ?></td>
                                    <td><?php echo htmlspecialchars($instructor['email']); ?></td>
                                    <td>
                                        <button type="button" class="text-red-500 hover:text-red-700 delete-btn" data-id="<?php echo $instructor['id']; ?>"><i class="fas fa-trash"></i> Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-8 max-w-sm w-full text-center">
            <h2 class="text-xl font-bold mb-4 text-red-600">Confirm Deletion</h2>
            <p class="mb-4">Please enter your admin password to delete this instructor.</p>
            <input type="password" id="adminPassword" class="w-full px-4 py-2 border border-gray-300 rounded mb-4" placeholder="Admin Password">
            <div id="deleteError" class="text-red-600 mb-2 hidden"></div>
            <div id="deleteSuccess" class="flex flex-col items-center justify-center mb-2 hidden">
                <span class="text-green-600 font-bold mt-2">Instructor deleted successfully!</span>
            </div>
            <div class="flex justify-center gap-4">
                <button type="button" id="cancelDeleteBtn" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 font-semibold">Cancel</button>
                <button type="button" id="confirmDeleteBtn" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700 font-semibold">Delete</button>
            </div>
        </div>
    </div>
    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-8 max-w-sm w-full text-center">
            <i class="fas fa-question-circle text-yellow-500 text-4xl mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Confirm Logout</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to log out?</p>
            <div class="flex justify-center space-x-4">
                <button id="confirmLogoutBtn" class="bg-red-600 text-white py-2 px-6 rounded-lg hover:bg-red-700 transition duration-200">Yes, Logout</button>
                <button id="cancelLogoutBtn" class="bg-gray-300 text-gray-800 py-2 px-6 rounded-lg hover:bg-gray-400 transition duration-200">Cancel</button>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let instructorToDelete = null;
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                instructorToDelete = this.getAttribute('data-id');
                document.getElementById('deleteModal').classList.remove('hidden');
                document.getElementById('adminPassword').value = '';
                document.getElementById('deleteError').classList.add('hidden');
                document.getElementById('deleteSuccess').classList.add('hidden');
            });
        });
        document.getElementById('cancelDeleteBtn').onclick = function() {
            document.getElementById('deleteModal').classList.add('hidden');
        };
        document.getElementById('confirmDeleteBtn').onclick = function() {
            const pwd = document.getElementById('adminPassword').value;
            document.getElementById('deleteError').classList.add('hidden');
            fetch('admin_instructors.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete_instructor&instructor_id=${instructorToDelete}&admin_password=${encodeURIComponent(pwd)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('deleteSuccess').classList.remove('hidden');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    document.getElementById('deleteError').textContent = data.message || 'Wrong password';
                    document.getElementById('deleteError').classList.remove('hidden');
                }
            })
            .catch(() => {
                document.getElementById('deleteError').textContent = 'An error occurred. Please try again.';
                document.getElementById('deleteError').classList.remove('hidden');
            });
        };
        // Logout confirmation modal logic
        const logoutLink = document.getElementById('adminLogoutLink');
        const logoutModal = document.getElementById('logoutModal');
        const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');
        const cancelLogoutBtn = document.getElementById('cancelLogoutBtn');
        if (logoutLink && logoutModal && confirmLogoutBtn && cancelLogoutBtn) {
            logoutLink.addEventListener('click', function(event) {
                event.preventDefault();
                logoutModal.classList.remove('hidden');
            });
            cancelLogoutBtn.addEventListener('click', function() {
                logoutModal.classList.add('hidden');
            });
            confirmLogoutBtn.addEventListener('click', function() {
                window.location.href = 'admin_logout.php';
            });
            // Optional: close modal if clicking outside modal content
            logoutModal.addEventListener('click', function(event) {
                if (event.target === logoutModal) {
                    logoutModal.classList.add('hidden');
                }
            });
        }
    });
    </script>
</body>
</html> 