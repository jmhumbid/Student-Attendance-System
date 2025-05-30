<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

$total_students = get_total_students_count();
$male_students = get_male_students_count();
$female_students = get_female_students_count();
$total_instructors = get_total_instructors_count();
$instructors = get_all_instructors();
$recent_logs = get_recent_log_entries(20);

// Find locked instructors
$locked_instructors = array_filter($instructors, function($inst) {
    return $inst['lockout_until'] && strtotime($inst['lockout_until']) > time();
});
$locked_count = count($locked_instructors);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - QR Lab</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar { min-width: 240px; }
        .sidebar-link.active, .sidebar-link:hover { background: #e5e7eb; color: #2563eb; }
        .card-icon { font-size: 2rem; }
        .stat-card {
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .stat-card:hover {
            box-shadow: 0 8px 24px 0 rgba(37, 99, 235, 0.15), 0 1.5px 4px 0 rgba(0,0,0,0.08);
            transform: translateY(-2px) scale(1.03);
        }
        .stat-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }
        .stat-cards-grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1rem;
        }
        .scrollable-log {
            max-height: 340px;
            overflow-y: auto;
            overflow-x: auto;
            width: 100%;
        }
        .log-table {
            min-width: 900px;
            width: 100%;
            table-layout: auto;
        }
        .log-table th, .log-table td {
            white-space: nowrap;
        }
        .log-table .user-agent-col {
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .container-100 {
            width: 100%;
            max-width: 100%;
        }
        @media (max-width: 900px) {
            .stat-card { min-width: 160px; }
            .stat-cards-grid-2 { grid-template-columns: 1fr; }
        }
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
            <a href="admin_dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg font-semibold active">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="admin_students.php" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg font-semibold">
                <i class="fas fa-users"></i> Registered Students
            </a>
            <a href="admin_instructors.php" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg font-semibold">
                <i class="fas fa-chalkboard-teacher"></i> Registered Instructors
            </a>
            <a href="register_instructor.php" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg font-semibold">
                <i class="fas fa-user-plus"></i> Register Instructor
            </a>
            <a href="#" id="logoutBtn" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg font-semibold text-red-600 mt-8">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </aside>
    <!-- Main Content -->
    <main class="flex-1 p-6 md:p-10">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Dashboard Overview</h1>
        <!-- Stat Cards: Students only -->
        <div class="stat-cards-grid mb-4">
            <div class="bg-white rounded-xl shadow stat-card p-4 flex flex-col items-center">
                <div class="bg-blue-100 text-blue-600 rounded-full p-3 mb-2 card-icon"><i class="fas fa-users"></i></div>
                <div class="text-2xl font-bold"><?php echo $total_students; ?></div>
                <div class="text-gray-600 mt-1 text-sm">Total Students</div>
                </div>
            <div class="bg-white rounded-xl shadow stat-card p-4 flex flex-col items-center">
                <div class="bg-blue-100 text-blue-600 rounded-full p-3 mb-2 card-icon"><i class="fas fa-male"></i></div>
                <div class="text-2xl font-bold"><?php echo $male_students; ?></div>
                <div class="text-gray-600 mt-1 text-sm">Male Students</div>
            </div>
            <div class="bg-white rounded-xl shadow stat-card p-4 flex flex-col items-center">
                <div class="bg-blue-100 text-blue-600 rounded-full p-3 mb-2 card-icon"><i class="fas fa-female"></i></div>
                <div class="text-2xl font-bold"><?php echo $female_students; ?></div>
                <div class="text-gray-600 mt-1 text-sm">Female Students</div>
            </div>
                </div>
        <!-- Stat Cards: Instructors and Locked Instructors below -->
        <div class="stat-cards-grid-2 mb-8">
            <div class="bg-white rounded-xl shadow stat-card p-4 flex flex-col items-center">
                <div class="bg-orange-100 text-orange-600 rounded-full p-3 mb-2 card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="text-2xl font-bold"><?php echo $total_instructors; ?></div>
                <div class="text-gray-600 mt-1 text-sm">Total Instructors</div>
            </div>
            <div class="bg-white rounded-xl shadow stat-card p-4 flex flex-col items-center">
                <div class="bg-red-100 text-red-600 rounded-full p-3 mb-2 card-icon"><i class="fas fa-lock"></i></div>
                <div class="text-2xl font-bold"><?php echo $locked_count; ?></div>
                <div class="text-gray-600 mt-1 text-sm">Locked Instructors</div>
            </div>
        </div>
        <!-- Locked Instructor Accounts -->
        <div class="bg-white rounded-xl shadow p-6 mb-8 container-100">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Locked Instructor Accounts</h2>
            <?php if ($locked_count > 0): ?>
                <?php foreach ($locked_instructors as $inst): ?>
                    <div class="mb-2">
                        <span class="font-semibold"><?php echo htmlspecialchars($inst['full_name']); ?> (<?php echo htmlspecialchars($inst['username']); ?>)</span><br>
                        <span class="text-gray-600 text-sm">Locked Until: <?php echo htmlspecialchars($inst['lockout_until']); ?></span>
                    </div>
                <?php endforeach; ?>
                                                <?php else: ?>
                <div class="text-gray-500">No locked instructor accounts.</div>
                                                <?php endif; ?>
                        </div>
        <!-- Recent Activity Log -->
        <div class="bg-white rounded-xl shadow p-6 container-100">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Activity Log</h2>
            <div class="scrollable-log">
                <table class="log-table bg-white border rounded-lg">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 border-b">#</th>
                            <th class="py-2 px-4 border-b">Timestamp</th>
                            <th class="py-2 px-4 border-b">User</th>
                            <th class="py-2 px-4 border-b">Type</th>
                            <th class="py-2 px-4 border-b">Action</th>
                            <th class="py-2 px-4 border-b">IP Address</th>
                            <th class="py-2 px-4 border-b user-agent-col">User Agent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_logs as $index => $log): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-2 px-4 border-b"><?php echo $index + 1; ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($log['timestamp']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($log['instructor_username'] ?? 'Admin'); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($log['user_type']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($log['action']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td class="py-2 px-4 border-b user-agent-col" title="<?php echo htmlspecialchars($log['user_agent']); ?>"><?php echo htmlspecialchars($log['user_agent']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
    </main>
    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-8 max-w-sm w-full text-center">
            <h2 class="text-xl font-bold mb-4">Confirm Logout</h2>
            <p class="mb-6">Are you sure you want to logout?</p>
            <div class="flex justify-center gap-4">
                <button id="cancelLogoutBtn" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 font-semibold">Cancel</button>
                <a href="admin_logout.php" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700 font-semibold">Logout</a>
            </div>
        </div>
    </div>
    <script>
        // Logout confirmation modal logic
        const logoutBtn = document.getElementById('logoutBtn');
        const logoutModal = document.getElementById('logoutModal');
        const cancelLogoutBtn = document.getElementById('cancelLogoutBtn');
        if (logoutBtn && logoutModal && cancelLogoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                logoutModal.classList.remove('hidden');
            });
            cancelLogoutBtn.addEventListener('click', function() {
                logoutModal.classList.add('hidden');
            });
        }
    </script>
</body>
</html> 