<?php
session_start();
require_once 'db.php'; // Ensure db.php is included before any use of $conn

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

// Fetch instructor info for profile picture
$stmt = $conn->prepare('SELECT email, full_name, profile_pic FROM instructors WHERE id = ?');
$stmt->bind_param('i', $instructor_id);
$stmt->execute();
$stmt->bind_result($email, $full_name, $profile_pic);
$stmt->fetch();
$stmt->close();

// Determine which image to show
if ($profile_pic && file_exists('profile_pics/' . $profile_pic)) {
    $profile_img_url = 'profile_pics/' . $profile_pic;
} else {
    $profile_img_url = 'profile_pics/default.png';
}

// Extract the first name
$first_name = $full_name;
if ($full_name) {
    $name_parts = explode(' ', $full_name);
    $first_name = $name_parts[0];
}

$class_id = $_GET['class_id'] ?? null;
$class = null;
$enrolled_students = [];
$all_students = [];
$error_message = '';
$success_message = '';

// Handle student enrollment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enrollStudent') {
    $student_id_to_enroll = trim($_POST['student_id_to_enroll'] ?? '');
    // Ensure $class_id is not null before attempting enrollment
    if ($class_id !== null && $student_id_to_enroll) {
        $enroll_result = enroll_student_in_class($class_id, $student_id_to_enroll);
        if ($enroll_result === true) {
            $_SESSION['success_message'] = 'Student enrolled successfully!';
        } else if ($enroll_result === 'duplicate') {
            $_SESSION['error_message'] = 'Student is already enrolled in this class.';
        } else {
            $_SESSION['error_message'] = 'Failed to enroll student: ' . ($enroll_result ?: 'Unknown error');
        }
    } else {
        $_SESSION['error_message'] = 'Invalid class or student selected.';
    }
    // Redirect to prevent form resubmission and show message, safely handling $class_id
    header('Location: instructor_class_details.php?class_id=' . ($class_id ?? ''));
    exit();
}
// Handle unenroll student form submission (moved up to prevent headers already sent)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'unenrollStudent') {
    $student_id_to_unenroll = $_POST['student_id_to_unenroll'] ?? null;
    if ($class_id && $student_id_to_unenroll) {
        $stmt = $conn->prepare('DELETE FROM class_students WHERE class_id = ? AND student_id = ?');
        $stmt->bind_param('ii', $class_id, $student_id_to_unenroll);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success_message'] = 'Student unenrolled successfully!';
        header('Location: instructor_class_details.php?class_id=' . $class_id);
        exit();
    }
}

// Check for and display messages (success or error) from session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}


if ($class_id !== null && $instructor_id) { // Ensure $class_id is not null here
    // Fetch class details
    $stmt = $conn->prepare('SELECT id, class_name, subject, start_time, end_time, instructor_id, background_image FROM classes WHERE id = ? AND instructor_id = ?');
    $stmt->bind_param('ii', $class_id, $instructor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $class = $result->fetch_assoc();
    $stmt->close();

    if ($class) {
        // Fetch enrolled students for this class
        $enrolled_students = get_enrolled_students($class_id);
        
        // Fetch all students for the enrollment dropdown
        $all_students = get_all_students();

    } else {
        $error_message = 'Class not found or you do not have permission to view it.';
    }
} else if ($class_id !== null) { // Handle case where class_id is provided but instructor_id is missing (shouldn't happen with login check but for safety)
     $error_message = 'Instructor ID missing.';
} else {
    // This case is for when no class_id is provided in the URL
    // No error message needed here, as the HTML will prompt to select a class
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($class['class_name'] ?? 'Class Details'); ?> - Instructor Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar {
            width: 260px;
            flex-shrink: 0;
            background-color: #1f2937;
            color: #f3f4f6;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex-grow: 1;
            overflow-y: auto;
            padding: 2rem;
            background-color: #f3f4f6;
        }
        .sidebar-link {
             display: flex;
             align-items: center;
             padding: 0.5rem 1rem;
             border-radius: 0.25rem;
             transition: background-color 0.2s, color 0.2s;
             color: #d1d5db;
             text-decoration: none;
         }
         .sidebar-link:hover {
             color: #ffffff;
             background-color: #4b5563;
         }
        .sidebar-link.active {
            background-color: #4338ca;
            color: #ffffff;
        }
         .sidebar-link i {
             margin-right: 0.75rem;
         }
         .class-card {
             background-color: #ffffff;
             border-radius: 0.5rem;
             padding: 1.5rem;
             box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
         }

        /* Dark Mode Styles */
         body.dark .main-content {
             background-color: #1a1a1a;
         }
         body.dark .text-gray-800 { color: #e5e7eb; }
         body.dark .text-gray-600 { color: #d1d5db; }
         body.dark .class-card { background-color: #262626; }
         body.dark .select2-container--default .select2-selection--single {
             background-color: #262626;
             border-color: #4b5563;
             color: #e5e7eb;
         }
         body.dark .select2-container--default .select2-selection--single .select2-selection__rendered {
             color: #e5e7eb;
         }
          body.dark .select2-dropdown {
              background-color: #262626;
              border-color: #4b5563;
          }
          body.dark .select2-results__option--highlighted[aria-selected] {
              background-color: #4b5563 !important;
              color: #ffffff !important;
          }
         body.dark .select2-search input {
             background-color: #1a1a1a !important;
             color: #e5e7eb !important;
             border-color: #4b5563 !important;
         }

        /* Pikaday custom styles */
        .pika-title select {
            /* Style for month/year dropdowns */
            color: #1f2937;
            /* Add other Tailwind-like styles as needed */
            padding: 0.25rem;
            border-radius: 0.25rem;
            border: 1px solid #d1d5db;
            background-color: #ffffff;
            margin: 0 0.25rem;
        }

        .pika-prev, .pika-next {
            /* Style for prev/next month buttons */
            color: #4f46e5;
            /* Add other Tailwind-like styles */
            padding: 0.5rem;
            transition: color 0.2s;
        }

        .pika-prev:hover, .pika-next:hover {
            color: #4338ca;
        }

        .pika-button {
             /* Default day button style */
             background-color: transparent;
             border: none;
             color: #1f2937;
             padding: 0.5rem;
             border-radius: 0.25rem;
             transition: background-color 0.2s, color 0.2s;
        }

        .pika-button:hover {
            background-color: #e5e7eb;
            color: #1f2937;
        }

        .is-today .pika-button {
            /* Style for today's date */
            font-weight: bold;
            color: #2563eb;
        }

        .is-selected .pika-button {
            /* Style for selected date */
            background-color: #4f46e5;
            color: #ffffff;
            font-weight: bold;
        }

        .is-selected .pika-button:hover {
             background-color: #4338ca;
             color: #ffffff;
        }

        .is-disabled .pika-button {
             /* Style for disabled dates */
             color: #9ca3af;
             cursor: default;
        }

        .is-disabled .pika-button:hover {
             background-color: transparent;
        }

        /* Dark mode styles for Pikaday */
         /*
         body.dark .pika-title select {
             color: #e5e7eb;
             border-color: #4b5563;
             background-color: #262626;
         }
         body.dark .pika-prev, body.dark .pika-next {
             color: #a78bfa;
         }
         body.dark .pika-prev:hover, body.dark .pika-next:hover {
             color: #c4b5fd;
         }
         */
    </style>
</head>
<body class="bg-gradient-to-br from-blue-100 via-purple-100 to-pink-100 min-h-screen flex">

    <!-- Sidebar -->
    <div class="sidebar bg-gray-800 text-gray-100 p-6 flex flex-col">
        <div class="flex items-center mb-8">
            <img src="<?php echo htmlspecialchars($profile_img_url); ?>" alt="Instructor Icon" class="w-10 h-10 rounded-full mr-3">
            <span class="text-xl font-semibold text-white"><?php echo htmlspecialchars($first_name); ?></span>
        </div>
        <nav class="flex flex-col space-y-4">
             <a href="instructor_dashboard.php" class="sidebar-link flex items-center py-2 px-4 rounded transition duration-200 text-gray-300 hover:text-white hover:bg-gradient-to-r hover:from-purple-500 hover:to-blue-500 hover:shadow-lg">
                <i class="fas fa-home mr-3"></i> Dashboard</a>
             <a href="#" class="sidebar-link flex items-center py-2 px-4 rounded transition duration-200 bg-purple-700 text-white"><i class="fas fa-chalkboard-teacher mr-3"></i> Class Details</a>
             <a href="attendance_scanner.php" class="sidebar-link flex items-center py-2 px-4 rounded transition duration-200 text-gray-300 hover:text-white hover:bg-gradient-to-r hover:from-purple-500 hover:to-blue-500 hover:shadow-lg"><i class="fas fa-qrcode mr-3"></i> Attendance Scanner</a>
             <!-- Settings Section -->
             <div class="mt-4">
                 <button id="settingsBtn" class="sidebar-link w-full text-left"><i class="fas fa-cog"></i> Settings</button>
                 <div id="settingsDropdown" class="hidden pl-8 mt-2 space-y-2">
                     <button id="privacyBtn" class="sidebar-link w-full text-left"><i class="fas fa-shield-alt"></i> Privacy and Security</button>
                     <div id="privacyDropdown" class="hidden pl-8 mt-2 space-y-2">
                         <a href="change_password.php" class="sidebar-link"><i class="fas fa-key"></i> Change Password</a>
                     </div>
                 </div>
             </div>
        </nav>
        <div class="mt-auto">
            <a href="instructor_logout.php" id="instructorLogoutLink" class="flex items-center py-2 px-4 rounded transition duration-200 text-red-400 hover:text-red-600 hover:bg-gray-700"><i class="fas fa-sign-out-alt mr-3"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-1 p-8 flex flex-col gap-8">
        <?php if ($error_message): ?>
            <div class="bg-red-100 text-red-700 rounded-lg p-3 mb-4 text-center font-semibold">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="bg-green-100 text-green-700 rounded-lg p-3 mb-4 text-center font-semibold">
                <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($class): ?>
            <!-- Class Banner with Glass Overlay -->
            <div class="relative rounded-2xl shadow-2xl mb-8 overflow-hidden min-h-[180px] flex items-end" style="background: url('class_backgrounds/<?php echo htmlspecialchars($class['background_image'] ?? 'bg1.jpg'); ?>') center/cover;">
                <!-- Only a dark gradient at the top for text readability -->
                <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-transparent to-transparent"></div>
                <div class="relative z-10 p-8">
                    <h1 class="text-3xl font-extrabold text-white mb-2 drop-shadow">Class: <?php echo htmlspecialchars($class['class_name']); ?></h1>
                    <p class="text-white mb-1 drop-shadow"><i class="fas fa-book mr-2"></i>Subject: <?php echo htmlspecialchars($class['subject']); ?></p>
                    <p class="text-white drop-shadow"><i class="fas fa-clock mr-2"></i>Time: <?php echo htmlspecialchars(date('g:i A', strtotime($class['start_time']))); ?> - <?php echo htmlspecialchars(date('g:i A', strtotime($class['end_time']))); ?></p>
                </div>
            </div>

            <!-- Enroll Student Section -->
            <div class="glass-card bg-white/80 rounded-2xl shadow-lg p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-user-plus text-blue-500"></i>Enroll Student</h2>
                <form action="instructor_class_details.php?class_id=<?php echo htmlspecialchars($class_id ?? ''); ?>" method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="enrollStudent">
                    <div class="icon-input">
                        <i class="fas fa-user"></i>
                        <select name="student_id_to_enroll" id="studentSelect" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" required>
                            <option value="">-- Select Student --</option>
                            <?php foreach ($all_students as $student): ?>
                                <option value="<?php echo htmlspecialchars($student['id']); ?>"><?php echo htmlspecialchars($student['name']); ?> (<?php echo htmlspecialchars($student['student_id']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-purple-500 text-white py-3 px-4 rounded-lg hover:from-purple-500 hover:to-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-semibold transition text-lg flex items-center justify-center gap-2">
                        <i class="fas fa-user-plus mr-2"></i>Enroll Student
                    </button>
                </form>
            </div>

            <!-- Enrolled Students Section -->
            <div class="glass-card bg-white/80 rounded-2xl shadow-lg p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-users text-green-500"></i>Enrolled Students</h2>
                <?php if (empty($enrolled_students)): ?>
                    <p class="text-gray-600">No students enrolled in this class yet.</p>
                <?php else: ?>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($enrolled_students as $student): ?>
                            <li class="py-3 flex justify-between items-center">
                                <span class="text-gray-800 flex items-center gap-2"><i class="fas fa-user-circle text-purple-400"></i><?php echo htmlspecialchars($student['name']); ?> <span class="text-gray-400 text-xs">(<?php echo htmlspecialchars($student['student_id']); ?>)</span></span>
                                <button type="button" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-700 transition unenroll-btn flex items-center gap-1" data-student-id="<?php echo $student['id']; ?>"><i class="fas fa-user-minus"></i> Unenroll</button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Attendance Calendar Section -->
            <div class="glass-card bg-white/80 rounded-2xl shadow-lg p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-calendar-alt text-pink-500"></i>Attendance Calendar</h2>
                <!-- FullCalendar container -->
                <div id="attendanceCalendar"></div>
                <div id="attendanceList" class="mt-6">
                    <p class="text-gray-600">Click a date on the calendar to view attendance records.</p>
                </div>
            </div>

        <?php else: ?>
            <div class="bg-yellow-100 text-yellow-700 rounded-lg p-3 mb-4 text-center font-semibold">
                <i class="fas fa-exclamation-triangle mr-2"></i>Please select a valid class to view details.
            </div>
        <?php endif; ?>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="instructorLogoutModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden flex items-center justify-center z-50">
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

    <!-- Unenroll Student Modal -->
    <div id="unenrollModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-8 max-w-sm w-full text-center">
            <h2 class="text-xl font-bold mb-4 text-red-600">Unenroll Student</h2>
            <p class="mb-6">Are you sure you want to unenroll this student from the class?</p>
            <form id="unenrollForm" method="POST" action="instructor_class_details.php?class_id=<?php echo htmlspecialchars($class_id); ?>">
                <input type="hidden" name="action" value="unenrollStudent">
                <input type="hidden" name="student_id_to_unenroll" id="unenrollStudentIdInput">
                <button type="button" id="cancelUnenrollBtn" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 font-semibold mr-2">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700 font-semibold">Unenroll</button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- FullCalendar CSS/JS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Logout Modal Logic
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

            // Initialize Select2 for student enrollment dropdown
             $('#studentSelect').select2({
                 placeholder: "Select a student to enroll",
                 allowClear: true // Add clear button
             });

            // FullCalendar integration for attendance
            const calendarEl = document.getElementById('attendanceCalendar');
            const attendanceList = document.getElementById('attendanceList');
            const classId = <?php echo json_encode($class_id); ?>;
            if (calendarEl && classId) {
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    height: 500,
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: ''
                    },
                    dateClick: function(info) {
                        // Fetch attendance for the clicked date
                        const dateStr = info.dateStr;
                        attendanceList.innerHTML = '<p class="text-gray-600">Loading attendance...</p>';
                        fetch(`get_attendance_by_date.php?class_id=${classId}&date=${dateStr}`)
                            .then(response => response.json())
                            .then(data => {
                                attendanceList.innerHTML = '';
                                if (data.success) {
                                    if (data.attendance && data.attendance.length > 0) {
                                        const studentListHtml = data.attendance.map(student => {
                                            const statusColor = student.status === 'Present' ? 'text-green-600' : 'text-red-600';
                                            let timeInfo = '';
                                            if (student.status === 'Present' && student.scan_time) {
                                                timeInfo = ` <span class=\"text-xs text-gray-500\">at ${student.scan_time}</span>`;
                                            }
                                            return `<p class=\"text-gray-800\">${student.name} (<span class=\"font-mono text-sm\">${student.student_id}</span>) - <span class=\"font-semibold ${statusColor}\">${student.status}</span>${timeInfo}</p>`;
                                        }).join('');
                                        attendanceList.innerHTML = studentListHtml;
                                    } else {
                                        attendanceList.innerHTML = '<p class="text-gray-600">No attendance recorded for this date.</p>';
                                    }
                                } else {
                                    attendanceList.innerHTML = `<p class="text-red-600">Error: ${data.error || 'Failed to fetch attendance'}</p>`;
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching attendance:', error);
                                attendanceList.innerHTML = '<p class="text-red-600">An error occurred while fetching attendance.</p>';
                            });
                    },
                });
                calendar.render();
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

            // Unenroll Modal Logic
            const unenrollBtns = document.querySelectorAll('.unenroll-btn');
            const unenrollModal = document.getElementById('unenrollModal');
            const cancelUnenrollBtn = document.getElementById('cancelUnenrollBtn');
            const unenrollStudentIdInput = document.getElementById('unenrollStudentIdInput');
            unenrollBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const studentId = this.getAttribute('data-student-id');
                    unenrollStudentIdInput.value = studentId;
                    unenrollModal.classList.remove('hidden');
                });
            });
            if (cancelUnenrollBtn && unenrollModal) {
                cancelUnenrollBtn.addEventListener('click', function() {
                    unenrollModal.classList.add('hidden');
                });
                unenrollModal.addEventListener('click', function(event) {
                    if (event.target === unenrollModal) {
                        unenrollModal.classList.add('hidden');
                    }
                });
            }

        }); // End DOMContentLoaded
    </script>

</body>
</html>