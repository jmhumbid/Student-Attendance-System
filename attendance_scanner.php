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

// Fetch classes for the logged-in instructor
$classes = [];
if ($instructor_id) {
    $classes = get_instructor_classes($instructor_id);
}

// Determine the currently selected class ID
$selected_class_id = $_GET['class_id'] ?? null;
$selected_class = null;

// If a class ID is selected, fetch its details
if ($selected_class_id) {
    foreach ($classes as $class) {
        if ($class['id'] == $selected_class_id) {
            $selected_class = $class;
            break;
        }
    }
    // If the selected class ID is not in the instructor's classes, reset it
    if (!$selected_class) {
        $selected_class_id = null;
        $error_message = "Invalid class selected.";
    }
}

$error_message = '';
$success_message = '';

// Handle messages from session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Scanner - Instructor Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            transition: background-color 0.3s, color 0.3s;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            width: 100%;
            max-width: 512px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            padding: 32px;
            transition: background-color 0.3s;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1d4ed8;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        #darkModeToggle {
            margin-left: 16px;
            padding: 8px;
            border-radius: 9999px;
            background-color: #e5e7eb;
            transition: background-color 0.3s;
            border: none;
            cursor: pointer;
        }
        #darkModeToggle:hover { background-color: #d1d5db; }
        #darkModeIcon { color: #4b5563; }

        .scanner-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 24px;
        }
        .scanner-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin-bottom: 16px;
        }
        #startCameraBtn, #stopScanningBtn {
            font-weight: 600;
            padding: 8px 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }
        #startCameraBtn {
            background-color: #2563eb;
            color: #ffffff;
        }
        #startCameraBtn:hover { background-color: #1d4ed8; }
        #stopScanningBtn {
            background-color: #10b981;
            color: #ffffff;
        }
        #stopScanningBtn:hover { background-color: #059669; }

        .video-container {
            position: relative;
            width: 400px;
            height: 300px;
            overflow: hidden;
            border-radius: 8px;
            border: 1px solid #bfdbfe;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .scanner-frame {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 70%;
            height: 70%;
            border: 2px dashed rgba(255, 255, 255, 0.8);
            box-sizing: border-box;
            pointer-events: none;
            z-index: 10;
        }
        .scanner-frame::before, .scanner-frame::after,
        .scanner-frame > ::before, .scanner-frame > ::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border-color: #ffffff;
            border-style: solid;
        }
        .scanner-frame::before { top: 0; left: 0; border-width: 2px 0 0 2px; }
        .scanner-frame::after { top: 0; right: 0; border-width: 2px 2px 0 0; }
        .scanner-frame > ::before { bottom: 0; right: 0; border-width: 0 2px 2px 0; }
        .scanner-frame > ::after { bottom: 0; left: 0; border-width: 0 0 2px 2px; }

        #video, #canvas {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .message {
            text-align: center;
            color: #dc2626;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .instruction-text {
            text-align: center;
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 16px;
        }

        .class-selector {
            margin-bottom: 24px;
            padding: 16px;
            background-color: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .class-selector select {
            width: 100%;
            padding: 8px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-bottom: 12px;
        }
        .class-selector button {
            width: 100%;
            padding: 8px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .class-selector button:hover {
            background-color: #1d4ed8;
        }

        .attendance-result {
            margin-top: 16px;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
        }
        .attendance-result.success {
            background-color: #dcfce7;
            color: #166534;
        }
        .attendance-result.error {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .tips-section {
            background-color: #e0f2f7;
            border-radius: 8px;
            padding: 16px;
            margin-top: 24px;
        }
        .tips-section h2 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #083344;
            margin-bottom: 12px;
        }
        .tips-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .tips-section li {
            display: flex;
            align-items: flex-start;
            margin-bottom: 8px;
            color: #075985;
        }
        .tips-section li i {
            margin-right: 8px;
            margin-top: 4px;
            color: #0ea5e9;
        }

        .back-link-container {
            text-align: center;
            margin-top: 24px;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            color: #2563eb;
            font-weight: 600;
            transition: color 0.3s;
            text-decoration: none;
        }
        .back-link:hover { color: #1d4ed8; }

        /* Dark Mode Styles */
        body.dark-mode {
            background-color: #0a0a0a;
            color: #e5e7eb;
        }
        body.dark-mode .container {
            background-color: #1a1a1a;
        }
        body.dark-mode .header h1 {
            color: #60a5fa;
        }
        body.dark-mode #darkModeToggle {
            background-color: #262626;
        }
        body.dark-mode #darkModeToggle:hover { background-color: #404040; }
        body.dark-mode #darkModeIcon { color: #fcd34d; }
        body.dark-mode .message { color: #f87171; }
        body.dark-mode .class-selector {
            background-color: #262626;
            border-color: #404040;
        }
        body.dark-mode .class-selector select {
            background-color: #1a1a1a;
            border-color: #404040;
            color: #e5e7eb;
        }
        body.dark-mode .tips-section {
            background-color: #075985;
            color: #e0f2f7;
        }
        body.dark-mode .tips-section h2 {
            color: #e0f2f7;
        }
        body.dark-mode .tips-section li {
            color: #bfdbfe;
        }
        body.dark-mode .tips-section li i {
            color: #7dd3fc;
        }
        body.dark-mode .back-link {
            color: #60a5fa;
        }
        body.dark-mode .back-link:hover { color: #93c5fd; }
    </style>
</head>
<body id="body-root">
<div class="container">
    <div class="header">
        <h1><span><i class="fas fa-qrcode"></i></span> Attendance Scanner</h1>
        <button id="darkModeToggle" class="p-2 rounded-full transition" title="Toggle dark mode">
            <i id="darkModeIcon" class="fas fa-moon"></i>
        </button>
    </div>

    <?php if ($error_message): ?>
        <div class="message"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <div class="message" style="color: #059669;"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <!-- Class Selection -->
    <div class="class-selector">
        <form method="GET" action="attendance_scanner.php">
            <select name="class_id" id="classSelect" required>
                <option value="">-- Select a Class --</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= $class['id'] ?>" <?= $selected_class_id == $class['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($class['class_name']) ?> (<?= htmlspecialchars($class['subject']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Select Class</button>
        </form>
    </div>

    <?php if ($selected_class): ?>
        <div class="scanner-section">
            <div class="scanner-controls">
                <button id="startCameraBtn" style="display: block;">
                    <i class="fas fa-camera"></i> Start Scanner
                </button>
                <button id="stopScanningBtn" style="display: none;">
                    <i class="fas fa-stop-circle"></i> Stop Scanner
                </button>
                <span id="instructionText" class="instruction-text" style="display: none;">Position QR code within frame</span>
            </div>
            <div class="video-container">
                <div class="scanner-frame"></div>
                <video id="video" playsinline style="display: none;"></video>
                <canvas id="canvas" style="display: none;"></canvas>
            </div>
            <div id="attendanceResult" class="attendance-result"></div>
        </div>

        <div class="tips-section">
            <h2>Tips for Scanning</h2>
            <ul>
                <li><i class="fas fa-lightbulb"></i> Ensure good lighting for better scanning</li>
                <li><i class="fas fa-lightbulb"></i> Hold the QR code steady within the frame</li>
                <li><i class="fas fa-lightbulb"></i> Keep the QR code clean and undamaged</li>
            </ul>
        </div>
    <?php endif; ?>

    <div class="back-link-container">
        <a href="instructor_dashboard.php" class="back-link"><i class="fas fa-arrow-left mr-2"></i>Back to Dashboard</a>
    </div>

</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dark mode toggle logic
    const bodyRoot = document.getElementById('body-root');
    const darkModeToggle = document.getElementById('darkModeToggle');
    const darkModeIcon = document.getElementById('darkModeIcon');

    function setDarkMode(on) {
        if (on) {
            bodyRoot.classList.add('dark-mode');
            localStorage.setItem('darkMode', '1');
            darkModeIcon.classList.remove('fa-moon');
            darkModeIcon.classList.add('fa-sun');
        } else {
            bodyRoot.classList.remove('dark-mode');
            localStorage.setItem('darkMode', '0');
            darkModeIcon.classList.remove('fa-sun');
            darkModeIcon.classList.add('fa-moon');
        }
    }

    // On load, apply saved preference or default to light
    if (localStorage.getItem('darkMode') === '1') {
        setDarkMode(true);
    } else {
        setDarkMode(false);
    }

    if (darkModeToggle) {
        darkModeToggle.onclick = function() {
            setDarkMode(!bodyRoot.classList.contains('dark-mode'));
        };
    }

    // Scanner Logic
    const startCameraBtn = document.getElementById('startCameraBtn');
    const stopScanningBtn = document.getElementById('stopScanningBtn');
    const instructionText = document.getElementById('instructionText');
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');
    const attendanceResult = document.getElementById('attendanceResult');

    let currentStream = null;
    let scanning = false;
    let scanInterval = null;

    // Class times (from PHP)
    const classStartTime = '<?= $selected_class ? $selected_class['start_time'] : '' ?>';
    const classEndTime = '<?= $selected_class ? $selected_class['end_time'] : '' ?>';

    console.log('Class Start Time:', classStartTime);
    console.log('Class End Time:', classEndTime);

    // Function to check if current time is within class time
    function isClassActive() {
        // Temporarily bypass time check to enable scanner
        return true;

        /* Original logic:
        if (!classStartTime || !classEndTime) return false;

        const now = new Date();
        const [startHours, startMinutes] = classStartTime.split(':').map(Number);
        const [endHours, endMinutes] = classEndTime.split(':').map(Number);

        const startTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), startHours, startMinutes);
        const endTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), endHours, endMinutes);

        const isActive = now >= startTime && now <= endTime;
        console.log('Current Time:', now);
        console.log('Start Time:', startTime);
        console.log('End Time:', endTime);
        console.log('Is class active?', isActive);
        return isActive;
        */
    }

    // Function to update scanner state based on class time
    function updateScannerState() {
        const isActive = isClassActive();
        if (isActive) {
            instructionText.textContent = 'Class is active. Scanner ready.';
            startCameraBtn.disabled = false;
            startCameraBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            console.log('Scanner button enabled.');
        } else {
            instructionText.textContent = 'Scanner is currently inactive. Class is not in session.';
            startCameraBtn.disabled = true;
            startCameraBtn.classList.add('opacity-50', 'cursor-not-allowed');
            console.log('Scanner button disabled.');
            if (scanning) {
                stopScanning();
            }
        }
    }

    async function startScanning() {
        console.log('Attempting to start scanning...');
        if (!isClassActive()) {
            attendanceResult.textContent = 'Cannot start scanner. Class is not in session.';
            attendanceResult.className = 'attendance-result error';
            return;
        }

        // Clear previous result messages
        attendanceResult.textContent = '';
        attendanceResult.className = 'attendance-result';

        try {
            // Request camera access
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            });

            // Set up video stream
            video.srcObject = stream;
            currentStream = stream;

            // Wait for video to be ready
            await new Promise((resolve) => {
                video.onloadedmetadata = () => {
                    video.play();
                    resolve();
                };
            });

            // Set up canvas
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            // Update UI
            scanning = true;
            startCameraBtn.style.display = 'none';
            stopScanningBtn.style.display = 'block';
            instructionText.style.display = 'block';
            video.style.display = 'block';
            canvas.style.display = 'block';

            // Start scanning loop
            scanInterval = setInterval(scanQRCode, 100);

        } catch (err) {
            console.error("Camera access error:", err);
            attendanceResult.textContent = 'Error accessing camera. Please check permissions and ensure a camera is available.' + (err.message ? ' Details: ' + err.message : '');
            attendanceResult.className = 'attendance-result error';
            stopScanning(); // Ensure cleanup even on failure
        }
    }

    function stopScanning() {
        scanning = false;
        
        // Clear scanning interval
        if (scanInterval) {
            clearInterval(scanInterval);
            scanInterval = null;
        }

        // Stop video stream
        if (currentStream) {
            currentStream.getTracks().forEach(track => track.stop());
            currentStream = null;
        }

        // Clear video and canvas
        video.srcObject = null;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        video.style.display = 'none';
        canvas.style.display = 'none';

        // Update UI
        startCameraBtn.style.display = 'block';
        stopScanningBtn.style.display = 'none';
        instructionText.style.display = 'none';

        updateScannerState();
    }

    function scanQRCode() {
        if (!scanning || !video || !canvas || !ctx) return;

        try {
            // Draw video frame to canvas
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Get image data
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            
            // Scan for QR code
            const code = jsQR(imageData.data, canvas.width, canvas.height);
            
            if (code) {
                console.log("QR Code detected:", code.data);
                recordAttendance(code.data);
            }
        } catch (e) {
            console.error("Error scanning QR code:", e);
        }
    }

    function recordAttendance(studentId) {
        if (!scanning) return;

        const classId = parseInt('<?= $selected_class_id ?? 'null' ?>', 10);
        if (isNaN(classId)) {
            attendanceResult.textContent = 'Error: Invalid class ID';
            attendanceResult.className = 'attendance-result error';
            return;
        }

        // Temporarily stop scanning while processing
        scanning = false;
        clearInterval(scanInterval);

        $.ajax({
            url: './record_attendance.php',
            method: 'POST',
            data: {
                class_id: classId,
                student_id: studentId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    attendanceResult.textContent = 'Attendance recorded for ' + response.student_name;
                    attendanceResult.className = 'attendance-result success';
                } else {
                    attendanceResult.textContent = response.message || 'Failed to record attendance';
                    attendanceResult.className = 'attendance-result error';
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", error);
                attendanceResult.textContent = 'Error recording attendance. Please try again.';
                attendanceResult.className = 'attendance-result error';
            },
            complete: function() {
                // Resume scanning after 2 seconds if class is still active
                setTimeout(() => {
                    if (isClassActive()) {
                        scanning = true;
                        scanInterval = setInterval(scanQRCode, 100);
                    } else {
                        stopScanning();
                    }
                }, 2000);
            }
        });
    }

    // Event Listeners
    if (startCameraBtn) {
        console.log('Attaching click listener to startCameraBtn');
        startCameraBtn.addEventListener('click', startScanning);
    }
    if (stopScanningBtn) {
        console.log('Attaching click listener to stopScanningBtn');
        stopScanningBtn.addEventListener('click', stopScanning);
    }

    // Initial state check
    if (startCameraBtn) {
        console.log('Performing initial scanner state check.');
        updateScannerState();
        // Check class status every minute
        setInterval(updateScannerState, 60000);
    }

    console.log('DOMContentLoaded finished. Script initialized.');
});
</script>
</body>
</html> 