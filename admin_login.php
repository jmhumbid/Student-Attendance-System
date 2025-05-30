<?php
session_start();
$error = '';

// Require db.php for logging function
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
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
        // Fetch admin from database
        $stmt = $conn->prepare('SELECT id, password FROM admin WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;

            // Log successful admin login
            add_log_entry(NULL, 'admin', 'Login Success', $_SERVER['REMOTE_ADDR'] ?? NULL, $_SERVER['HTTP_USER_AGENT'] ?? NULL);

            header('Location: admin_dashboard.php');
            exit();
        } else {
            $error = 'Invalid username or password.';

            // Log failed admin login attempt
            add_log_entry(NULL, 'admin', 'Login Failed', $_SERVER['REMOTE_ADDR'] ?? NULL, $_SERVER['HTTP_USER_AGENT'] ?? NULL);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .logo-animate { animation: logo-bounce 1.2s infinite alternate; }
        @keyframes logo-bounce { 0% { transform: translateY(0); } 100% { transform: translateY(-8px); } }
    </style>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md mx-auto bg-white rounded-2xl shadow-2xl p-8 mt-8">
        <div class="flex flex-col items-center mb-8">
            <div class="logo-animate mb-4">
                <img src="https://cdn-icons-png.flaticon.com/512/3135/3135768.png" alt="Logo" class="w-16 h-16 rounded-full shadow-lg border-4 border-blue-200 bg-white">
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Admin Login</h1>
        </div>
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 rounded-lg p-3 mb-4 text-center font-semibold">
                <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-gray-700 font-semibold mb-1">Username</label>
                <input type="text" name="username" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="Enter username">
            </div>
            <div>
                <label class="block text-gray-700 font-semibold mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200" placeholder="Enter password">
            </div>
            <div class="g-recaptcha" data-sitekey="6LeE-T4rAAAAAAkmiiiGhJ_iv79HuOUtnbxVwPTt"></div>
            <button type="submit" class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform transition duration-200 hover:scale-[1.02] flex items-center justify-center gap-2">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        <div class="text-center mt-6">
            <a href="index.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold transition"><i class="fas fa-arrow-left mr-2"></i>Back to Dashboard</a>
        </div>
    </div>
</body>
</html> 