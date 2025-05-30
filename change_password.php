<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['instructor_logged_in']) || $_SESSION['instructor_logged_in'] !== true) {
    header('Location: instructor_login.php');
    exit();
}

$instructor_id = $_SESSION['instructor_id'] ?? null;
$instructor_name = $_SESSION['instructor_name'] ?? 'Instructor';

require_once 'db.php';

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

$error_message = '';
$success_message = '';

// Handle password change form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Get current instructor data by email for consistency with login
    $instructor = get_instructor_by_email($email);
    
    if ($instructor && $current_password === base64_decode($instructor['password'])) {
        if ($new_password === $confirm_password) {
            // Check password complexity
            $minLength = 8;
            $hasUppercase = preg_match('/[A-Z]/', $new_password);
            $hasLowercase = preg_match('/[a-z]/', $new_password);
            $hasNumber = preg_match('/[0-9]/', $new_password);
            
            // Check for presence of at least one symbol
            $hasSymbol = false;
            $symbols = '$&+,:;=?@#|\'<>.^*()%!-`~'; // Common symbols
            for ($i = 0; $i < strlen($new_password); $i++) {
                if (strpos($symbols, $new_password[$i]) !== false) {
                    $hasSymbol = true;
                    break;
                }
            }

            if (strlen($new_password) < $minLength || !$hasUppercase || !$hasLowercase || !$hasNumber || !$hasSymbol) {
                $error_message = 'New password must be at least ' . $minLength . ' characters long and include uppercase, lowercase, numbers, and symbols.';
            } else {
                // Pass the raw new password (not base64 encoded)
                $result = update_instructor_password($instructor_id, $new_password);
                if ($result === true) {
                    // Log the password change in admin logs
                    add_log_entry($instructor_id, 'instructor', 'Password Changed', $_SERVER['REMOTE_ADDR'] ?? NULL, $_SERVER['HTTP_USER_AGENT'] ?? NULL);
                    $success_message = 'Password updated successfully!';
                } else {
                    $error_message = 'Failed to update password: ' . $result;
                }
            }
        } else {
            $error_message = 'New passwords do not match.';
        }
    } else {
        $error_message = 'Current password is incorrect.';
    }
}

// Add PHPMailer includes and use statements at the top
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Instructor Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Flowbite CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.18);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        .icon-input {
            position: relative;
        }
        .icon-input i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #a5b4fc;
        }
        .icon-input input {
            padding-left: 2.5rem;
        }
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
                <i class="fas fa-home mr-3"></i> Dashboard
            </a>
            <a href="instructor_class_details.php" class="sidebar-link flex items-center py-2 px-4 rounded transition duration-200 text-gray-300 hover:text-white hover:bg-gradient-to-r hover:from-purple-500 hover:to-blue-500 hover:shadow-lg">
                <i class="fas fa-chalkboard-teacher mr-3"></i> Class Details
            </a>
            <a href="attendance_scanner.php" class="sidebar-link flex items-center py-2 px-4 rounded transition duration-200 text-gray-300 hover:text-white hover:bg-gradient-to-r hover:from-purple-500 hover:to-blue-500 hover:shadow-lg">
                <i class="fas fa-qrcode mr-3"></i> Attendance Scanner
            </a>
            <!-- Settings Section -->
            <div class="mt-4">
                <button id="settingsBtn" class="sidebar-link w-full text-left transition duration-200 text-gray-300 hover:text-white hover:bg-gradient-to-r hover:from-purple-500 hover:to-blue-500 hover:shadow-lg">
                    <i class="fas fa-cog"></i> Settings
                </button>
                <div id="settingsDropdown" class="hidden pl-8 mt-2 space-y-2">
                    <button id="privacyBtn" class="sidebar-link w-full text-left transition duration-200 text-gray-300 hover:text-white hover:bg-gradient-to-r hover:from-purple-500 hover:to-blue-500 hover:shadow-lg">
                        <i class="fas fa-shield-alt"></i> Privacy and Security
                    </button>
                    <div id="privacyDropdown" class="hidden pl-8 mt-2 space-y-2">
                        <a href="change_password.php" class="sidebar-link transition duration-200 text-gray-300 hover:text-white hover:bg-gradient-to-r hover:from-green-400 hover:to-blue-500 hover:shadow-lg">
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
    <div class="main-content flex-1 flex items-center justify-center p-8">
        <div class="glass-card w-full max-w-lg mx-auto p-10 shadow-2xl relative">
            <div class="flex flex-col items-center mb-8">
                <div class="bg-gradient-to-tr from-blue-400 via-purple-400 to-pink-400 p-4 rounded-full shadow-lg mb-4">
                    <i class="fas fa-key text-white text-3xl"></i>
                </div>
                <h1 class="text-3xl font-extrabold text-gray-800 mb-2 tracking-tight text-center">Change Password</h1>
                <p class="text-gray-500 text-center mb-2">Update your password for better security.</p>
            </div>
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
            <form method="POST" class="space-y-6">
                <div class="icon-input">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="current_password" id="current_password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="Current Password">
                </div>
                <div class="icon-input">
                    <i class="fas fa-shield-alt"></i>
                    <input type="password" name="new_password" id="new_password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="New Password">
                </div>
                <div class="icon-input">
                    <i class="fas fa-check"></i>
                    <input type="password" name="confirm_password" id="confirm_password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="Confirm New Password">
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-purple-500 text-white py-3 px-4 rounded-lg hover:from-purple-500 hover:to-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-semibold transition text-lg flex items-center justify-center gap-2">
                    <i class="fas fa-save mr-2"></i>Update Password
                </button>
            </form>
        </div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
        });
    </script>
</body>
</html> 