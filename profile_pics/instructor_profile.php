<?php
session_start();
if (!isset($_SESSION['instructor_logged_in']) || !$_SESSION['instructor_logged_in']) {
    header('Location: instructor_login.php');
    exit();
}
require_once 'db.php';
$instructor_id = $_SESSION['instructor_id'];

// Fetch instructor info
$stmt = $conn->prepare('SELECT email, full_name, profile_pic FROM instructors WHERE id = ?');
$stmt->bind_param('i', $instructor_id);
$stmt->execute();
$stmt->bind_result($email, $full_name, $profile_pic);
$stmt->fetch();
$stmt->close();

$success = '';
$error = '';

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['profile_pic'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($ext, $allowed)) {
        $new_name = 'instructor_' . $instructor_id . '_' . time() . '.' . $ext;
        $target = 'profile_pics/' . $new_name;
        if (!is_dir('profile_pics')) mkdir('profile_pics');
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $stmt = $conn->prepare('UPDATE instructors SET profile_pic = ? WHERE id = ?');
            $stmt->bind_param('si', $new_name, $instructor_id);
            $stmt->execute();
            $stmt->close();
            $profile_pic = $new_name;
            $success = 'Profile picture updated!';
        } else {
            $error = 'Failed to upload image.';
        }
    } else {
        $error = 'Invalid file type. Only jpg, jpeg, png, gif allowed.';
    }
}

// Determine which image to show
if ($profile_pic && file_exists('profile_pics/' . $profile_pic)) {
    $profile_img_url = 'profile_pics/' . $profile_pic;
} else {
    $profile_img_url = 'profile_pics/default.png';
}

// Extract the first name for sidebar
$first_name = $full_name;
if ($full_name) {
    $name_parts = explode(' ', $full_name);
    $first_name = $name_parts[0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
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
        .icon-input input[type="file"] {
            padding-left: 2.5rem;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-100 via-purple-100 to-pink-100 min-h-screen flex">
    <!-- Sidebar - Same as instructor_dashboard.php -->
    <div class="sidebar bg-gray-800 text-gray-100 p-6 flex flex-col">
        <div class="flex items-center mb-8">
            <img src="<?php echo htmlspecialchars($profile_img_url); ?>" alt="Instructor Icon" class="w-10 h-10 rounded-full mr-3">
            <span class="text-xl font-semibold text-white"><?php echo htmlspecialchars($first_name); ?></span>
        </div>
        <nav class="flex flex-col space-y-4">
            <a href="instructor_dashboard.php" class="sidebar-link flex items-center py-2 px-4 rounded transition duration-200 text-gray-300 hover:text-white hover:bg-gradient-to-r hover:from-purple-500 hover:to-blue-500 hover:shadow-lg">
                <i class="fas fa-home mr-3"></i> Dashboard
            </a>
            <a href="#" class="sidebar-link flex items-center py-2 px-4 rounded transition duration-200 text-gray-300 hover:text-white hover:bg-gradient-to-r hover:from-purple-500 hover:to-blue-500 hover:shadow-lg">
                <i class="fas fa-chalkboard-teacher mr-3"></i> Classes
            </a>
            <a href="attendance_scanner.php" class="sidebar-link flex items-center py-2 px-4 rounded transition duration-200 text-gray-300 hover:text-white hover:bg-gradient-to-r hover:from-purple-500 hover:to-blue-500 hover:shadow-lg">
                <i class="fas fa-qrcode mr-3"></i> Attendance Scanner
            </a>
            <a href="instructor_profile.php" class="sidebar-link flex items-center py-2 px-4 rounded transition duration-200 bg-purple-700 text-white">
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
    <div class="main-content flex-1 flex items-center justify-center p-8">
        <div class="glass-card w-full max-w-lg mx-auto p-10 shadow-2xl relative">
            <div class="flex flex-col items-center mb-8">
                <div class="bg-gradient-to-tr from-blue-400 via-purple-400 to-pink-400 p-4 rounded-full shadow-lg mb-4">
                    <img src="<?php echo htmlspecialchars($profile_img_url); ?>" alt="Profile Picture" class="w-32 h-32 rounded-full shadow-lg border-4 border-blue-200 object-cover mb-2">
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($full_name); ?></h2>
                <p class="text-gray-500 mb-2"><?php echo htmlspecialchars($email); ?></p>
            </div>
            <?php if ($success): ?>
                <div class="bg-green-100 text-green-700 rounded-lg p-3 mb-4 text-center font-semibold">
                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-100 text-red-700 rounded-lg p-3 mb-4 text-center font-semibold">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="icon-input">
                    <i class="fas fa-image"></i>
                    <input type="file" name="profile_pic" accept="image/*" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-purple-500 text-white py-3 px-4 rounded-lg hover:from-purple-500 hover:to-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-semibold transition text-lg flex items-center justify-center gap-2">
                    <i class="fas fa-upload mr-2"></i>Update Picture
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
    // Sidebar settings/Privacy dropdown logic (copied from dashboard)
    document.addEventListener('DOMContentLoaded', function() {
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