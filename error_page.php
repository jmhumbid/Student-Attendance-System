<?php
$error_type = htmlspecialchars($_GET['type'] ?? 'Generic Error');
$error_message = htmlspecialchars($_GET['message'] ?? 'An unexpected error occurred.');

$title = 'Error - ' . $error_type;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md mx-auto bg-white rounded-2xl shadow-2xl p-8 text-center">
        <div class="flex flex-col items-center mb-6">
            <i class="fas fa-exclamation-triangle text-red-500 text-6xl mb-4"></i>
            <h1 class="text-3xl font-bold text-gray-800 mb-2"><?= $error_type ?></h1>
            <p class="text-gray-600 mb-6"><?= $error_message ?></p>
        </div>
        <a href="index.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold transition">
            <i class="fas fa-home mr-2"></i> Go to Home
        </a>
    </div>
</body>
</html> 