<?php
session_start();

// Set session timeout (e.g., 30 minutes = 1800 seconds)
$session_timeout = 1800;

// Check if last activity timestamp is set
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_timeout) {
    // Session has expired
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header('Location: error_page.php?type=Session Expired&message=Your session has expired. Please log in again.');
    exit();
}

// Update last activity timestamp
$_SESSION['last_activity'] = time();

// Redirect to login if not logged in
if (!isset($_SESSION['instructor_logged_in']) || $_SESSION['instructor_logged_in'] !== true) {
    header('Location: instructor_login.php');
    exit();
}

$instructor_id = $_SESSION['instructor_id'] ?? null;
$instructor_name = $_SESSION['instructor_name'] ?? 'Instructor';
require_once 'db.php'; // Ensure $conn is available before using it
$instructor_email = '';
if ($instructor_id) {
    $stmt = $conn->prepare('SELECT email, full_name, profile_pic FROM instructors WHERE id = ?');
    $stmt->bind_param('i', $instructor_id);
    $stmt->execute();
    $stmt->bind_result($instructor_email, $instructor_name, $profile_pic);
    $stmt->fetch();
    $stmt->close();
}

// Determine which image to show
if ($profile_pic && file_exists('profile_pics/' . $profile_pic)) {
    $profile_img_url = 'profile_pics/' . $profile_pic;
} else {
    $profile_img_url = 'profile_pics/default.png';
}

// Extract the first name
$first_name = $instructor_name;
if ($instructor_name) {
    $name_parts = explode(' ', $instructor_name);
    $first_name = $name_parts[0];
}

// Handle Add Class form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'addClass') {
    $class_name = trim($_POST['class_name'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');
    $background_image = $_POST['background_image'] ?? null;

    if ($instructor_id && $class_name && $subject && $start_time && $end_time && $background_image) {
        $add_result = add_class($instructor_id, $class_name, $subject, $start_time, $end_time, $background_image);
        if ($add_result) {
            // Redirect after successful addition to prevent form resubmission
            $_SESSION['success_message'] = 'Class added successfully!';
            header('Location: instructor_dashboard.php');
            exit();
        } else {
            // Handle error (you might want more specific error handling here)
            $_SESSION['error_message'] = 'Failed to add class.';
            header('Location: instructor_dashboard.php');
            exit();
        }
    } else {
        $_SESSION['error_message'] = 'Please fill all class details.';
        header('Location: instructor_dashboard.php');
        exit();
    }
}

// Handle delete class form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deleteClass') {
    error_log('Delete class POST: ' . print_r($_POST, true));
    $delete_class_id = $_POST['class_id'] ?? null;
    if ($delete_class_id && $instructor_id) {
        if (delete_class($delete_class_id, $instructor_id)) {
            $_SESSION['success_message'] = 'Class deleted successfully!';
            header('Location: instructor_dashboard.php');
            exit();
        } else {
            $error_message = 'Failed to delete class.';
            error_log('Failed to delete class: ' . $delete_class_id . ' for instructor: ' . $instructor_id);
        }
    } else {
        $error_message = 'Class ID or instructor ID missing.';
        error_log('Delete class error: missing class_id or instructor_id. POST: ' . print_r($_POST, true));
    }
}

// Check for and display messages (success or error) from session
$success_message = '';
$error_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

$classes = [];
if ($instructor_id) {
    $classes = get_instructor_classes($instructor_id);
}

// --- Dashboard Stats Logic ---
$total_classes = count($classes);
// Get all students enrolled in this instructor's classes
$total_students = 0;
foreach ($classes as $class) {
    $students = get_enrolled_students($class['id']);
    $total_students += count($students);
}
// Optionally, get today's attendance (sum for all classes)
$today = date('Y-m-d');
$total_attendance_today = 0;
foreach ($classes as $class) {
    $attendance = get_class_attendance($class['id']);
    foreach ($attendance as $student_id => $timestamps) {
        foreach ($timestamps as $ts) {
            if (strpos($ts, $today) === 0) {
                $total_attendance_today++;
                break;
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Custom styles for sidebar width and main content area */
        .sidebar {
            width: 260px; /* Adjust as needed */
            flex-shrink: 0;
        }
        .main-content {
            flex-grow: 1;
            overflow-y: auto;
        }
        .sidebar-link.active,
        .sidebar-link:hover {
            background-color: #e0e7ff; /* Blue 100 */
            color: #3b82f6; /* Blue 500 */
        }
        .class-card {
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: transform 0.2s ease-in-out;
        }
        .class-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="min-h-screen flex bg-gradient-to-br from-blue-100 via-purple-100 to-pink-100">

    <!-- Sidebar - Uniform with Profile Page -->
    <div class="sidebar bg-gray-800 text-gray-100 p-6 flex flex-col">
        <div class="flex items-center mb-8">
            <img src="<?php echo htmlspecialchars($profile_img_url); ?>" alt="Instructor Icon" class="w-10 h-10 rounded-full mr-3">
            <span class="text-xl font-semibold text-white"><?php echo htmlspecialchars($first_name); ?></span>
        </div>
        <nav class="flex flex-col space-y-4">
            <a href="instructor_dashboard.php" class="sidebar-link flex items-center py-2 px-4 rounded transition duration-200 bg-purple-700 text-white">
                <i class="fas fa-home mr-3"></i> Dashboard
            </a>
            <a href="#" class="sidebar-link flex items-center py-2 px-4 rounded transition duration-200 text-gray-300 hover:text-white hover:bg-gradient-to-r hover:from-purple-500 hover:to-blue-500 hover:shadow-lg">
                <i class="fas fa-chalkboard-teacher mr-3"></i> Classes
            </a>
            <a href="attendance_scanner.php" class="sidebar-link flex items-center py-2 px-4 rounded transition duration-200 text-gray-300 hover:text-white hover:bg-gradient-to-r hover:from-purple-500 hover:to-blue-500 hover:shadow-lg">
                <i class="fas fa-qrcode mr-3"></i> Attendance Scanner
            </a>
            <a href="instructor_profile.php" class="sidebar-link flex items-center py-2 px-4 rounded transition duration-200 text-gray-300 hover:text-white hover:bg-gradient-to-r hover:from-green-400 hover:to-blue-500 hover:shadow-lg">
                <i class="fas fa-user-circle mr-3"></i> Profile
            </a>
            <!-- Settings Section -->
            <div class="mt-4">
                <button id="settingsBtn" class="sidebar-link w-full text-left">
                    <i class="fas fa-cog"></i> Settings
                </button>
                <div id="settingsDropdown" class="hidden pl-8 mt-2 space-y-2">
                    <button id="privacyBtn" class="sidebar-link w-full text-left">
                        <i class="fas fa-shield-alt"></i> Privacy and Security
                    </button>
                    <div id="privacyDropdown" class="hidden pl-8 mt-2 space-y-2">
                        <a href="change_password.php" class="sidebar-link">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                    </div>
                </div>
            </div>
        </nav>
        <div class="mt-auto">
            <a href="instructor_logout.php" id="instructorLogoutLink" class="flex items-center py-2 px-4 rounded transition duration-200 text-red-400 hover:text-red-600 hover:bg-gray-700">
                <i class="fas fa-sign-out-alt mr-3"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-1 p-8 flex flex-col gap-8">
        <!-- Welcome Banner -->
        <div class="glass-card w-full flex items-center gap-6 p-8 rounded-2xl shadow-2xl bg-white/70 backdrop-blur-lg border border-white/30 mb-4">
            <img src="<?php echo htmlspecialchars($profile_img_url); ?>" alt="Profile" class="w-20 h-20 rounded-full border-4 border-purple-300 shadow-lg">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-800 mb-1">Welcome, <?php echo htmlspecialchars($first_name); ?>!</h1>
                <p class="text-gray-500 text-lg">Have a productive day managing your classes.</p>
            </div>
        </div>
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gradient-to-tr from-blue-400 via-purple-400 to-pink-400 rounded-2xl shadow-xl p-6 flex flex-col items-center glass-card">
                <div class="bg-white/70 rounded-full p-4 mb-2 text-blue-700 shadow-lg"><i class="fas fa-chalkboard-teacher text-2xl"></i></div>
                <div class="text-3xl font-bold"><?php echo $total_classes; ?></div>
                <div class="text-gray-700 mt-1 text-lg">Total Classes</div>
            </div>
            <div class="bg-gradient-to-tr from-green-400 via-blue-400 to-purple-400 rounded-2xl shadow-xl p-6 flex flex-col items-center glass-card">
                <div class="bg-white/70 rounded-full p-4 mb-2 text-green-700 shadow-lg"><i class="fas fa-users text-2xl"></i></div>
                <div class="text-3xl font-bold"><?php echo $total_students; ?></div>
                <div class="text-gray-700 mt-1 text-lg">Total Students</div>
            </div>
            <div class="bg-gradient-to-tr from-pink-400 via-purple-400 to-blue-400 rounded-2xl shadow-xl p-6 flex flex-col items-center glass-card">
                <div class="bg-white/70 rounded-full p-4 mb-2 text-pink-700 shadow-lg"><i class="fas fa-calendar-check text-2xl"></i></div>
                <div class="text-3xl font-bold"><?php echo $total_attendance_today; ?></div>
                <div class="text-gray-700 mt-1 text-lg">Attendance Today</div>
            </div>
        </div>
        <!-- Classes List -->
        <div class="bg-white/80 rounded-2xl shadow-lg p-8">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <h2 class="text-2xl font-bold text-gray-800">Your Classes</h2>
                <button id="addClassBtn" class="bg-gradient-to-r from-blue-500 to-purple-500 text-white py-2 px-6 rounded-xl shadow-lg hover:from-purple-500 hover:to-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-semibold transition text-lg flex items-center gap-2">
                    <i class="fas fa-plus"></i> Add Class
                </button>
            </div>
            <?php if (empty($classes)): ?>
                <div class="flex flex-col items-center justify-center py-12">
                    <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="No Classes" class="w-24 h-24 mb-4 opacity-60">
                    <p class="text-gray-500 text-lg">You don't have any classes yet. Click <span class='font-bold text-purple-600'>Add Class</span> to create one.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($classes as $class): ?>
                        <div class="class-card relative overflow-hidden group shadow-xl rounded-2xl border border-white/30" style="background: url('class_backgrounds/<?php echo htmlspecialchars($class['background_image'] ?? 'bg1.jpg'); ?>') center/cover, #fff; min-height: 220px;">
                            <div class="absolute inset-0 bg-gradient-to-t from-white/90 via-white/60 to-transparent opacity-90 group-hover:opacity-100 transition duration-300"></div>
                            <div class="relative z-10 flex flex-col justify-end h-full p-6">
                                <h3 class="text-xl font-bold text-black mb-1"><?php echo htmlspecialchars($class['class_name']); ?></h3>
                                <p class="text-black mb-1">Subject: <?php echo htmlspecialchars($class['subject']); ?></p>
                                <p class="text-black mb-3">Time: <?php echo htmlspecialchars(date('g:i A', strtotime($class['start_time']))); ?> - <?php echo htmlspecialchars(date('g:i A', strtotime($class['end_time']))); ?></p>
                                <div class="flex gap-2 items-center">
                                    <a href="instructor_class_details.php?class_id=<?php echo $class['id']; ?>" class="text-black hover:underline font-semibold" title="View Details"><i class="fas fa-eye mr-1"></i> Details</a>
                                    <button type="button" class="ml-2 bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition delete-class-btn" data-class-id="<?php echo $class['id']; ?>" title="Delete Class"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Add Class Modal -->
    <div id="addClassModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden flex items-center justify-center">
        <div class="relative p-8 bg-white w-full max-w-md m-auto rounded-lg shadow-lg">
            <div class="text-center mb-4">
                 <h3 class="text-2xl font-semibold text-gray-800">Add New Class</h3>
            </div>
            <form method="POST" action="instructor_dashboard.php" class="space-y-6">
                 <input type="hidden" name="action" value="addClass">
                 <div>
                     <label for="className" class="block text-gray-700 font-semibold mb-1">Class Name</label>
                     <input type="text" id="className" name="class_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="e.g., Section A">
                 </div>
                 <div>
                     <label for="subject" class="block text-gray-700 font-semibold mb-1">Subject</label>
                     <input type="text" id="subject" name="subject" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="e.g., Calculus I">
                 </div>
                 <div>
                     <label for="startTime" class="block text-gray-700 font-semibold mb-1">Start Time</label>
                     <input type="time" id="startTime" name="start_time" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                 </div>
                 <div>
                     <label for="endTime" class="block text-gray-700 font-semibold mb-1">End Time</label>
                     <input type="time" id="endTime" name="end_time" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                 </div>
                 <div>
                     <label class="block text-gray-700 font-semibold mb-1 mb-2">Class Background</label>
                     <div class="flex gap-3">
                         <?php for ($i = 1; $i <= 5; $i++): ?>
                             <label class="cursor-pointer">
                                 <input type="radio" name="background_image" value="bg<?= $i ?>.jpg" class="hidden" required>
                                 <img src="class_backgrounds/bg<?= $i ?>.jpg" alt="Background <?= $i ?>" class="w-20 h-14 object-cover rounded border-2 border-transparent hover:border-blue-500 transition">
                             </label>
                         <?php endfor; ?>
                     </div>
                 </div>
                 <button type="submit" class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform transition duration-200 hover:scale-[1.02] flex items-center justify-center gap-2">
                     <i class="fas fa-plus mr-2"></i>Create Class
                 </button>
            </form>
            <button id="closeAddClassModal" class="mt-4 bg-gray-300 text-gray-800 py-2 px-6 rounded-lg hover:bg-gray-400 transition duration-200 w-full">Cancel</button>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="instructorLogoutModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden flex items-center justify-center">
        <div class="relative p-8 bg-white w-full max-w-sm m-auto rounded-lg shadow-lg">
            <div class="text-center">
                <i class="fas fa-question-circle text-yellow-500 text-4xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Confirm Logout</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to log out?</p>
                <div class="flex justify-center space-x-4">
                    <button id="confirmInstructorLogoutBtn" class="bg-red-600 text-white py-2 px-6 rounded-lg hover:bg-red-700 transition duration-200">Yes, Logout</button>
                    <button id="cancelInstructorLogoutBtn" class="bg-gray-300 text-gray-800 py-2 px-6 rounded-lg hover:bg-gray-400 transition duration-200">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Class Modal for Dashboard -->
    <div id="deleteClassModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-8 max-w-sm w-full text-center">
            <h2 class="text-xl font-bold mb-4 text-red-600">Delete Class</h2>
            <p class="mb-6">Are you sure you want to delete this class? This action cannot be undone.</p>
            <form id="deleteClassForm" method="POST" action="instructor_dashboard.php">
                <input type="hidden" name="action" value="deleteClass">
                <input type="hidden" name="class_id" id="deleteClassIdInput">
                <button type="button" id="cancelDeleteClassBtn" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 font-semibold mr-2">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700 font-semibold">Delete</button>
            </form>
        </div>
    </div>

</body>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const logoutLink = document.getElementById('instructorLogoutLink');
        const logoutModal = document.getElementById('instructorLogoutModal');
        const confirmLogoutBtn = document.getElementById('confirmInstructorLogoutBtn');
        const cancelLogoutBtn = document.getElementById('cancelInstructorLogoutBtn');

        if (logoutLink && logoutModal && confirmLogoutBtn && cancelLogoutBtn) {
            logoutLink.addEventListener('click', function(event) {
                event.preventDefault(); // Prevent default link behavior
                logoutModal.classList.remove('hidden');
            });

            cancelLogoutBtn.addEventListener('click', function() {
                logoutModal.classList.add('hidden');
            });

            confirmLogoutBtn.addEventListener('click', function() {
                window.location.href = 'instructor_logout.php'; // Proceed with logout
            });

            // Close modal if clicking outside the modal content
            logoutModal.addEventListener('click', function(event) {
                if (event.target === logoutModal) {
                    logoutModal.classList.add('hidden');
                }
            });
        }

        // Logic for Add Class Modal
        const addClassBtn = document.getElementById('addClassBtn');
        const addClassModal = document.getElementById('addClassModal');
        const closeAddClassModalBtn = document.getElementById('closeAddClassModal');

        if (addClassBtn && addClassModal && closeAddClassModalBtn) {
            addClassBtn.addEventListener('click', function() {
                addClassModal.classList.remove('hidden');
            });

            closeAddClassModalBtn.addEventListener('click', function() {
                addClassModal.classList.add('hidden');
            });

            addClassModal.addEventListener('click', function(event) {
                if (event.target === addClassModal) {
                    addClassModal.classList.add('hidden');
                }
            });
        }

        // Settings dropdown functionality
        const settingsBtn = document.getElementById('settingsBtn');
        const settingsDropdown = document.getElementById('settingsDropdown');
        const privacyBtn = document.getElementById('privacyBtn');
        const privacyDropdown = document.getElementById('privacyDropdown');

        if (settingsBtn && settingsDropdown) {
            settingsBtn.addEventListener('click', function() {
                settingsDropdown.classList.toggle('hidden');
            });
        }

        if (privacyBtn && privacyDropdown) {
            privacyBtn.addEventListener('click', function() {
                privacyDropdown.classList.toggle('hidden');
            });
        }

        // Delete Class Modal Logic for Dashboard
        const deleteBtns = document.querySelectorAll('.delete-class-btn');
        const deleteModal = document.getElementById('deleteClassModal');
        const cancelDeleteBtn = document.getElementById('cancelDeleteClassBtn');
        const deleteClassIdInput = document.getElementById('deleteClassIdInput');
        deleteBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const classId = this.getAttribute('data-class-id');
                deleteClassIdInput.value = classId;
                deleteModal.classList.remove('hidden');
            });
        });
        if (cancelDeleteBtn && deleteModal) {
            cancelDeleteBtn.addEventListener('click', function() {
                deleteModal.classList.add('hidden');
            });
            deleteModal.addEventListener('click', function(event) {
                if (event.target === deleteModal) {
                    deleteModal.classList.add('hidden');
                }
            });
        }
    });
</script>
</html> 