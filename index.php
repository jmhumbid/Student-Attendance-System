<?php
// dashboard.php - Main dashboard for Student Attendance System
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance System Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .card-animate {
            transition: transform 0.2s cubic-bezier(.4,2,.6,1), box-shadow 0.2s;
        }
        .card-animate:hover {
            transform: translateY(-8px) scale(1.04) rotate(-1deg);
            box-shadow: 0 8px 32px 0 rgba(0,0,0,0.15);
            background: linear-gradient(135deg, #e0e7ff 0%, #f0fdfa 100%);
        }
        .logo-animate {
            animation: logo-bounce 1.2s infinite alternate;
        }
        @keyframes logo-bounce {
            0% { transform: translateY(0); }
            100% { transform: translateY(-8px); }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div id="dashboard-root" class="w-full max-w-4xl mx-auto bg-white rounded-2xl shadow-2xl p-8 md:p-12 transition-opacity duration-500">
        <div class="flex flex-col items-center mb-10">
            <!-- Placeholder Logo -->
            <div class="logo-animate mb-4">
                <img src="https://cdn-icons-png.flaticon.com/512/3135/3135768.png" alt="Logo" class="w-20 h-20 rounded-full shadow-lg border-4 border-blue-200 bg-white">
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-2 tracking-tight text-center">Student Attendance System</h1>
            <p class="text-gray-500 text-center">© Skypian</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mt-8">
            <!-- Register Student Card -->
            <a href="register.php" class="card-animate group bg-white rounded-xl p-6 flex flex-col items-center shadow-md hover:shadow-xl cursor-pointer transition relative dashboard-link">
                <div class="bg-blue-100 text-blue-600 rounded-full p-4 mb-4 text-3xl group-hover:bg-blue-600 group-hover:text-white transition">
                    <i class="fas fa-user-plus"></i>
                </div>
                <span class="font-semibold text-lg text-gray-800 group-hover:text-blue-700 transition">Register Student</span>
            </a>
            <!-- Admin Card -->
            <a href="admin_login.php" class="card-animate group bg-white rounded-xl p-6 flex flex-col items-center shadow-md hover:shadow-xl cursor-pointer transition relative dashboard-link">
                <div class="bg-purple-100 text-purple-600 rounded-full p-4 mb-4 text-3xl group-hover:bg-purple-600 group-hover:text-white transition">
                    <i class="fas fa-user-shield"></i>
                </div>
                <span class="font-semibold text-lg text-gray-800 group-hover:text-purple-700 transition">Admin</span>
            </a>
            <!-- Instructor Card -->
            <a href="instructor_login.php" class="card-animate group bg-white rounded-xl p-6 flex flex-col items-center shadow-md hover:shadow-xl cursor-pointer transition relative dashboard-link">
                <div class="bg-green-100 text-green-600 rounded-full p-4 mb-4 text-3xl group-hover:bg-green-600 group-hover:text-white transition">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <span class="font-semibold text-lg text-gray-800 group-hover:text-green-700 transition">Instructor</span>
            </a>
            <!-- Scan QR Code Card -->
            <a href="scan.php" class="card-animate group bg-white rounded-xl p-6 flex flex-col items-center shadow-md hover:shadow-xl cursor-pointer transition relative dashboard-link">
                <div class="bg-cyan-100 text-cyan-600 rounded-full p-4 mb-4 text-3xl group-hover:bg-cyan-600 group-hover:text-white transition">
                    <i class="fas fa-qrcode"></i>
                </div>
                <span class="font-semibold text-lg text-gray-800 group-hover:text-cyan-700 transition">Scan QR Code</span>
            </a>
        </div>
    </div>
    <script>
    document.querySelectorAll('.dashboard-link').forEach(card => {
        card.addEventListener('click', function(e) {
            const target = this.getAttribute('href');
            if (target === 'scan.php') {
                // Instantly redirect for Scan QR Code card
                return;
            }
            e.preventDefault();
            const dashboard = document.getElementById('dashboard-root');
            dashboard.style.opacity = 0;
            setTimeout(() => {
                window.location.href = target;
            }, 500);
        });
    });
    </script>
</body>
</html> 