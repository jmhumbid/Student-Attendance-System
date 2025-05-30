<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin_login.php');
    exit();
}

// Initialize messages and clear session messages on page load
$error = '';
$success = '';

// Check for messages in session and display them
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Clear the message after displaying
}
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the message after displaying
}

require_once 'db.php'; // Ensure db.php is included

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $instructor_id = $_POST['instructor_id'] ?? '';
    $department = $_POST['department'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($instructor_id)) {
        $errors[] = 'Instructor ID is required';
    } elseif (!preg_match('/^\d{4}-\d{5}$/', $instructor_id)) {
        $errors[] = 'Instructor ID must be in the format 1234-12345 and contain only numbers.';
    }
    
    if (empty($department)) {
        $errors[] = 'Department is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    } elseif (strtolower(substr($email, -12)) !== '@evsu.edu.ph') {
        $errors[] = 'Not an EVSU mail';
    }
    
    if (empty($username)) {
        $errors[] = 'Username is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } else {
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
            $errors[] = 'Password must contain at least one special character (!@#$%^&*()-_=+{};:,<.>)';
        }
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        $result = add_instructor($full_name, $instructor_id, $department, $email, $username, $password);
        if ($result === true) {
            // Log the registration in admin logs
            add_log_entry(NULL, 'admin', 'Registered Instructor: ' . $full_name . ' (' . $username . ')', $_SERVER['REMOTE_ADDR'] ?? NULL, $_SERVER['HTTP_USER_AGENT'] ?? NULL);
            $success = 'Registration successful!';
            // Do not redirect, just show modal and refresh after close
        } else {
            $errors[] = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Instructor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>body { font-family: 'Inter', sans-serif; } .sidebar { min-width: 240px; } .sidebar-link.active, .sidebar-link:hover { background: #e5e7eb; color: #2563eb; }</style>
</head>
<body class="bg-gray-50 min-h-screen flex">
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
            <a href="admin_instructors.php" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg font-semibold">
                <i class="fas fa-chalkboard-teacher"></i> Registered Instructors
            </a>
            <a href="register_instructor.php" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg font-semibold active">
                <i class="fas fa-user-plus"></i> Register Instructor
            </a>
            <a href="admin_logout.php" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg font-semibold text-red-600 mt-8">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </aside>
    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center">
        <div class="w-full max-w-md mx-auto bg-white rounded-2xl shadow-2xl p-8 mt-8">
            <div class="flex flex-col items-center mb-8">
                <div class="logo-animate mb-4">
                    <img src="https://cdn-icons-png.flaticon.com/512/3135/3135768.png" alt="Logo" class="w-16 h-16 rounded-full shadow-lg border-4 border-green-200 bg-white">
                </div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Register Instructor</h1>
            </div>
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 text-red-700 rounded-lg p-3 mb-4 text-center font-semibold">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <!-- Success Modal -->
                <div id="successModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-lg p-8 max-w-sm w-full text-center">
                        <h2 class="text-xl font-bold mb-4 text-green-600">Registration Successful!</h2>
                        <p class="mb-6">The instructor has been registered successfully.</p>
                        <button id="closeSuccessModal" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700 font-semibold">OK</button>
                    </div>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('closeSuccessModal').onclick = function() {
                        window.location.href = window.location.pathname;
                    };
                });
                </script>
            <?php endif; ?>
            <form method="POST" class="space-y-6">
                <div>
                    <label for="full_name" class="block text-gray-700 font-semibold mb-1">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200" placeholder="Enter full name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                </div>
                <div>
                    <label for="instructor_id" class="block text-gray-700 font-semibold mb-1">Instructor ID</label>
                    <input type="text" id="instructor_id" name="instructor_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200" placeholder="1234-12345" pattern="^\d{4}-\d{5}$" title="Format: 1234-12345 (numbers only)" value="<?php echo htmlspecialchars($_POST['instructor_id'] ?? ''); ?>">
                </div>
                <div>
                    <label for="department" class="block text-gray-700 font-semibold mb-1">Department</label>
                    <input type="text" id="department" name="department" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200" placeholder="Enter department" value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>">
                </div>
                <div>
                    <label for="email" class="block text-gray-700 font-semibold mb-1">Email</label>
                    <input type="email" id="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200" placeholder="Enter email address" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    <button type="button" id="verifyEmailBtn" class="mt-2 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Verify Email</button>
                    <div id="verifyEmailMsg" class="mt-2 text-sm"></div>
                    <div id="otpInputContainer" class="mt-2 hidden">
                        <input type="text" id="otpCode" class="w-full px-4 py-2 border border-gray-300 rounded-lg mt-2" placeholder="Enter verification code">
                        <button type="button" id="submitOtpBtn" class="mt-2 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">Submit Code</button>
                        <div id="otpMsg" class="mt-2 text-sm"></div>
                    </div>
                </div>
                <div>
                    <label for="username" class="block text-gray-700 font-semibold mb-1">Username</label>
                    <input type="text" id="username" name="username" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200" placeholder="Enter username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                <div>
                    <label for="password" class="block text-gray-700 font-semibold mb-1">Password</label>
                    <input type="password" id="password" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200" placeholder="Enter password">
                    <small class="form-text text-muted">
                        Password must be at least 8 characters long and contain:
                        <ul>
                            <li>At least one uppercase letter</li>
                            <li>At least one lowercase letter</li>
                            <li>At least one number</li>
                            <li>At least one special character (!@#$%^&*()-_=+{};:,<.>)</li>
                        </ul>
                    </small>
                </div>
                <div>
                    <label for="confirm_password" class="block text-gray-700 font-semibold mb-1">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200" placeholder="Enter confirm password">
                </div>
                <button type="submit" class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transform transition duration-200 hover:scale-[1.02] flex items-center justify-center gap-2">
                    <i class="fas fa-user-plus"></i> Register Instructor
                </button>
            </form>
        </div>
    </main>
    <script>
    let emailVerified = false;
    $('#verifyEmailBtn').on('click', function() {
        const email = $('#email').val();
        $('#otpInputContainer').removeClass('hidden'); // Show input immediately
        $('#verifyEmailMsg').text('Sending verification code...').removeClass('text-green-600 text-red-600').addClass('text-gray-600');
        $.post('send_otp_ajax.php', { email: email }, function(data) {
            if (data.success) {
                $('#verifyEmailMsg').text('Verification code sent! Check your email.').removeClass('text-red-600').addClass('text-green-600');
            } else {
                $('#verifyEmailMsg').text(data.message || 'Failed to send verification code.').removeClass('text-green-600').addClass('text-red-600');
            }
        }, 'json');
    });

    $('#submitOtpBtn').on('click', function() {
        const email = $('#email').val();
        const otp = $('#otpCode').val();
        $('#otpMsg').text('Verifying...').removeClass('text-green-600 text-red-600').addClass('text-gray-600');
        $.post('verify_otp_ajax.php', { email: email, otp: otp }, function(data) {
            if (data.success) {
                $('#otpMsg').text('Email verified!').removeClass('text-red-600').addClass('text-green-600');
                emailVerified = true;
            } else {
                $('#otpMsg').text(data.message || 'Invalid or expired code.').removeClass('text-green-600').addClass('text-red-600');
                emailVerified = false;
            }
        }, 'json');
    });

    // Prevent form submission if email is not verified
    $('form').on('submit', function(e) {
        if (!emailVerified) {
            e.preventDefault();
            alert('Please verify the email before registering.');
        }
    });
    </script>
</body>
</html> 