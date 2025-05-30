<?php
session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION['instructor_logged_in']) && $_SESSION['instructor_logged_in'] === true) {
    header('Location: instructor_dashboard.php');
    exit();
}

$error = '';
$success = '';

require_once 'db.php'; // Ensure db.php is included

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

    // Verify reCAPTCHA response
    $recaptcha_secret_key = '6LeE-T4rAAAAAIXkY2C81klaxi8pGZZDVfkBYbAg'; // Replace with your Secret Key
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';

    $recaptcha_data = [
        'secret' => $recaptcha_secret_key,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($recaptcha_data)
        ]
    ];

    $context = stream_context_create($options);
    $recaptcha_result = file_get_contents($recaptcha_url, false, $context);
    $recaptcha_json = json_decode($recaptcha_result, true);

    if (!$recaptcha_json['success']) {
        $error = 'Please complete the reCAPTCHA.';
    } else {
        // reCAPTCHA successful, proceed with login logic
        if ($email && $password) {
            $instructor = get_instructor_by_email($email);

            if ($instructor) {
                // Check if account is locked
                if ($instructor['lockout_until'] && strtotime($instructor['lockout_until']) > time()) {
                    $error = 'Account is locked. Please try again later.';
                } else {
                    // Account is not locked or lockout time has passed
                    if ($password === base64_decode($instructor['password'])) {
                        // Login successful
                        // Reset failed attempts and lockout time
                        reset_failed_login_attempts($instructor['id']);

                        $_SESSION['instructor_logged_in'] = true;
                        $_SESSION['instructor_id'] = $instructor['id'];
                        $_SESSION['instructor_username'] = $instructor['username'];
                        $_SESSION['instructor_name'] = $instructor['full_name'];

                        // Log successful login
                        add_log_entry($instructor['id'], 'instructor', 'Login Success', $_SERVER['REMOTE_ADDR'] ?? NULL, $_SERVER['HTTP_USER_AGENT'] ?? NULL);

                        // Redirect directly to dashboard
                        header('Location: instructor_dashboard.php');
                        exit();
                    } else {
                        // Incorrect password
                        record_failed_login_attempt($instructor['id']);
                        // Log failed login attempt (with user_id if found)
                        $user_id = $instructor['id'] ?? NULL;
                        add_log_entry($user_id, 'instructor', 'Login Failed', $_SERVER['REMOTE_ADDR'] ?? NULL, $_SERVER['HTTP_USER_AGENT'] ?? NULL);

                        $error = 'Invalid email or password.';
                    }
                }
            } else {
                // Instructor not found
                // Log failed login attempt (user_id is NULL)
                add_log_entry(NULL, 'instructor', 'Login Failed', $_SERVER['REMOTE_ADDR'] ?? NULL, $_SERVER['HTTP_USER_AGENT'] ?? NULL);

                // For security, don't indicate if email exists or not
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Please enter email and password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
<div class="w-full max-w-md mx-auto bg-white rounded-2xl shadow-2xl p-8 mt-8">
    <div class="flex flex-col items-center mb-8">
        <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Instructor Icon" class="w-16 h-16 rounded-full shadow-lg border-4 border-purple-200 bg-white mb-4">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Instructor Login</h1>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 rounded-lg p-3 mb-4 text-center font-semibold">
            <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <div>
            <label for="email" class="block text-gray-700 font-semibold mb-1">Email</label>
            <input type="email" id="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition duration-200" placeholder="Enter your EVSU email address">
        </div>
        <div>
            <label for="password" class="block text-gray-700 font-semibold mb-1">Password</label>
            <input type="password" id="password" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition duration-200" placeholder="Enter password">
        </div>
        <div class="g-recaptcha" data-sitekey="6LeE-T4rAAAAAAkmiiiGhJ_iv79HuOUtnbxVwPTt"></div>
        <button type="submit" class="w-full bg-purple-600 text-white py-3 px-4 rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transform transition duration-200 hover:scale-[1.02] flex items-center justify-center gap-2">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
    </form>
    <div class="text-center mt-6">
        <a href="index.php" class="inline-flex items-center text-purple-600 hover:text-purple-800 font-semibold transition"><i class="fas fa-arrow-left mr-2"></i>Back to Main Site</a>
    </div>
</div>
</body>
</html> 