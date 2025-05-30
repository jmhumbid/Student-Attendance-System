<?php
// index.php - Student registration and QR code generation
require_once 'db.php';
session_start();

$message = '';
$student_id = '';
$name = '';
$qr_image = '';

// Handle redirect after POST
if (isset($_SESSION['registered_student_id'])) {
    $student_id = $_SESSION['registered_student_id'];
    $name = $_SESSION['registered_name'] ?? ''; // Also pass name for display
    $message = $_SESSION['registered_message'] ?? '';
    unset($_SESSION['registered_student_id'], $_SESSION['registered_message'], $_SESSION['registered_name']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $course = trim($_POST['course'] ?? '');

    if ($student_id && $name && $gender && $year && $course) {
        if (add_student($student_id, $name, $gender, $year, $course)) {
            $_SESSION['registered_student_id'] = $student_id;
            $_SESSION['registered_name'] = $name; // Store name in session
            $_SESSION['registered_message'] = 'Student registered successfully!';
            header('Location: register.php');
            exit();
        } else {
            $message = 'Student ID already exists. Please use a different ID.';
        }
    } else {
        $message = 'Please fill all fields.';
    }
}
$registration_success = $message === 'Student registered successfully!' && !empty($student_id);
$student_id_for_qr = $student_id; // Variable name for QR code data
$error_message = $message !== 'Student registered successfully!' && !empty($message) ? $message : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- QRCode.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <!-- html2canvas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
        .qr-container {
            border: 5px solid maroon; /* Adjust color as needed */
            padding: 20px;
            background-color: white;
            display: block; /* To wrap around content */
            margin-top: 20px;
            margin: 0 auto;
            text-align: center; /* Center inline-block children */
        }
        .qr-header {
            text-align: center;
            margin-bottom: 15px; /* Restore a reasonable margin */
        }
        .evsu-logo {
            width: 80px; /* Adjust size as needed */
            height: auto;
            margin-bottom: 10px;
            display: block;
            margin: 0 auto;
        }
        .qr-title {
            font-size: 18px; /* Adjust size as needed */
            font-weight: bold;
            color: maroon; /* Adjust color as needed */
        }
        .qr-subtitle {
             font-size: 14px; /* Adjust size as needed */
             color: maroon; /* Adjust color as needed */
             margin-top: 5px;
        }
        .qr-border {
            border: 5px solid maroon; /* Border around QR code */
            padding: 10px; /* Add small inner padding */
            display: inline-block; /* Make border wrap tightly around QR code */
            text-align: center; /* Center content inside the border */
            margin-bottom: 15px; /* Add some space below the border */
        }
         #qrcode {
            width: 300px;
            height: 300px;
            margin: 0 auto; /* Center the QR code */
        }
        .download-button-container {
            text-align: center;
            margin-top: 20px;
        }
        .download-btn {
            /* Style your download button */
             background-color: #10B981; /* Green-600 */
             color: white;
             padding: 12px 24px;
             border-radius: 8px;
             display: inline-flex;
             align-items: center;
             gap: 8px;
             font-size: 1rem;
             font-weight: 600;
             cursor: pointer;
             transition: background-color 0.2s ease-in-out;
        }
        .download-btn:hover {
            background-color: #059669; /* Green-700 */
        }
         .success-message {
            background-color: #D1FAE5; /* Green-100 */
            color: #065F46; /* Green-700 */
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
         .error-message {
            background-color: #FEE2E2; /* Red-100 */
            color: #991B1B; /* Red-700 */
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

    </style>
</head>
<body class="bg-gradient-to-br from-blue-100 via-purple-100 to-pink-100 min-h-screen flex flex-col items-center justify-center">
    <div class="w-full max-w-2xl mx-auto mt-8">
        <a href="index.php" class="inline-flex items-center mb-6 text-blue-600 hover:text-blue-800 font-semibold transition">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </a>
        <!-- Search Registered Students -->
        <div class="bg-white/80 rounded-2xl shadow-lg p-6 mb-8 flex flex-col gap-4">
            <h2 class="text-xl font-bold text-gray-800 mb-2 flex items-center gap-2"><i class="fas fa-search text-blue-500"></i>Search Registered Students</h2>
            <div class="relative">
                <input type="text" id="student-search" autocomplete="off" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 pr-12" placeholder="Type a name to search...">
                <i class="fas fa-search absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <div id="autocomplete-list" class="absolute left-0 right-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg z-10 hidden"></div>
            </div>
            <div id="selected-student" class="mt-2"></div>
        </div>
        <!-- Registration Card -->
        <div x-data="{
            showQR: <?php echo $registration_success ? 'true' : 'false' ?>,
            showMessage: <?php echo !empty($message) ? 'true' : 'false' ?>,
            messageType: '<?php echo $registration_success ? 'success' : 'error' ?>',
        }" class="bg-white/80 rounded-2xl shadow-lg overflow-hidden transform transition-all duration-300 hover:shadow-xl p-8">
            <div class="text-center mb-8">
                <i class="fas fa-user-graduate text-4xl text-blue-600 mb-4"></i>
                <h2 class="text-3xl font-bold text-gray-800">Student Registration</h2>
            </div>
            <!-- Alert Message -->
             <?php if (!empty($message)): ?>
                <div
                 x-show="showMessage"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-90"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 :class="messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                 class="rounded-lg p-4 mb-6 flex items-center"
                >
                <i :class="messageType === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'" class="mr-2"></i>
                <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="space-y-6">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 flex items-center">
                        <i class="fas fa-id-card mr-2 text-blue-600"></i>
                        Student ID
                    </label>
                    <input type="text" id="student_id" name="student_id" required
                           maxlength="10"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                           placeholder="0000-00000"
                           value="<?php echo htmlspecialchars($student_id); ?>">
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 flex items-center">
                        <i class="fas fa-user mr-2 text-blue-600"></i>
                        Full Name
                    </label>
                    <input type="text" name="name" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                           placeholder="Enter your full name"
                            value="<?php echo htmlspecialchars($name); ?>">
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 flex items-center">
                        <i class="fas fa-venus-mars mr-2 text-blue-600"></i>
                        Gender
                    </label>
                    <select name="gender" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 flex items-center">
                        <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>
                        Year Level
                    </label>
                    <select name="year" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                        <option value="">Select Year Level</option>
                        <option value="1st Year" <?php echo (isset($_POST['year']) && $_POST['year'] === '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                        <option value="2nd Year" <?php echo (isset($_POST['year']) && $_POST['year'] === '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                        <option value="3rd Year" <?php echo (isset($_POST['year']) && $_POST['year'] === '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                        <option value="4th Year" <?php echo (isset($_POST['year']) && $_POST['year'] === '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 flex items-center">
                        <i class="fas fa-graduation-cap mr-2 text-blue-600"></i>
                        Course
                    </label>
                    <select name="course" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200">
                        <option value="">Select Course</option>
                        <option value="Bachelor of Secondary Education" <?php echo (isset($_POST['course']) && $_POST['course'] === 'Bachelor of Secondary Education') ? 'selected' : ''; ?>>Bachelor of Secondary Education</option>
                        <option value="Bachelor of Science in Information Technology" <?php echo (isset($_POST['course']) && $_POST['course'] === 'Bachelor of Science in Information Technology') ? 'selected' : ''; ?>>Bachelor of Science in Information Technology</option>
                        <option value="Bachelor of Science in Civil Engineering" <?php echo (isset($_POST['course']) && $_POST['course'] === 'Bachelor of Science in Civil Engineering') ? 'selected' : ''; ?>>Bachelor of Science in Civil Engineering</option>
                        <option value="Bachelor of Science in Mechanical Engineering" <?php echo (isset($_POST['course']) && $_POST['course'] === 'Bachelor of Science in Mechanical Engineering') ? 'selected' : ''; ?>>Bachelor of Science in Mechanical Engineering</option>
                        <option value="Bachelor of Science in Electrical Engineering" <?php echo (isset($_POST['course']) && $_POST['course'] === 'Bachelor of Science in Electrical Engineering') ? 'selected' : ''; ?>>Bachelor of Science in Electrical Engineering</option>
                        <option value="Bachelor of Physical Education" <?php echo (isset($_POST['course']) && $_POST['course'] === 'Bachelor of Physical Education') ? 'selected' : ''; ?>>Bachelor of Physical Education</option>
                        <option value="Bachelor of Industrial Technology" <?php echo (isset($_POST['course']) && $_POST['course'] === 'Bachelor of Industrial Technology') ? 'selected' : ''; ?>>Bachelor of Industrial Technology</option>
                    </select>
                </div>
                <button type="submit"
                        class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform transition duration-200 hover:scale-[1.02] flex items-center justify-center">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Register & Generate QR
                </button>
            </form>
            <!-- QR Code Section -->
            <?php if ($registration_success): ?>
                <div class="qr-container mx-auto">
                    <div class="qr-header">
                        <img src="evsulogo/evsu-logo.jpg" alt="EVSU Logo" class="evsu-logo">
                        <div class="qr-title">EASTERN VISAYAS STATE UNIVERSITY-OCC</div>
                    </div>
                    <div class="qr-border">
                        <div id="qrcode"></div>
                    </div>
                    <div class="student-info text-center mt-4">
                        <div class="student-id-text font-bold text-lg text-gray-800">ID: <?php echo htmlspecialchars($student_id); ?></div>
                        <div class="student-name-text text-gray-700">Name: <?php echo htmlspecialchars($name); ?></div>
                    </div>
                </div>
                <div class="download-button-container">
                    <button onclick="downloadFullQR()" class="download-btn">
                        <i class="fas fa-download"></i> Download QR Card
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
    // Student ID auto-format: 0000-00000 (4 digits - 5 digits)
    const studentIdInput = document.getElementById('student_id');
    if (studentIdInput) {
        studentIdInput.addEventListener('input', function(e) {
            let value = this.value.replace(/[^0-9]/g, '');
            value = value.slice(0, 9); // max 9 digits
            if (value.length > 4) {
                value = value.slice(0, 4) + '-' + value.slice(4);
            }
            this.value = value;
        });
    }

    // --- QR Code Generation and Download ---
    <?php if ($registration_success): ?>
        // Generate QR Code
        var qrcode = new QRCode(document.getElementById("qrcode"), {
            text: "<?php echo htmlspecialchars($student_id_for_qr); ?>",
            width: 300,
            height: 300,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H,
            margin: 4,
            quietZone: 15
        });

        // Download function
        function downloadFullQR() {
            const element = document.querySelector('.qr-container');

            html2canvas(element, {
                scale: 3, // Increase scale for better resolution
                backgroundColor: '#ffffff', // Set background color
                logging: false,
                useCORS: true, // Needed for images like the logo
                allowTaint: true,
                imageTimeout: 15000, // Increase timeout for image loading
                removeContainer: false,
                imageSmoothingEnabled: false,
                letterRendering: true
            }).then(canvas => {
                // Add text overlay (Student ID and Name)
                const ctx = canvas.getContext('2d');

                // Calculate text positions below the QR code
                const textX = canvas.width / 2;
                // Calculate vertical position below the QR code and border within the captured element
                const qrContainerHeight = element.offsetHeight;
                const textY = qrContainerHeight * 3 + 30; // Position text 30px below the captured element (scaled)

                ctx.font = 'bold 30px sans-serif'; // Adjust font size and style
                ctx.fillStyle = '#000000'; // Text color
                ctx.textAlign = 'center';
                ctx.fillText("ID: <?php echo htmlspecialchars($student_id_for_qr); ?>", textX, textY);

                ctx.font = '25px sans-serif'; // Adjust font size and style for name
                ctx.fillStyle = '#000000'; // Text color
                ctx.textAlign = 'center';
                ctx.fillText("Name: <?php echo htmlspecialchars($name); ?>", textX, textY + 40); // Adjust vertical spacing


                canvas.toBlob(function(blob) {
                    const link = document.createElement('a');
                    link.download = 'EVSU-QRCard-<?php echo htmlspecialchars($student_id_for_qr); ?>.png';
                    link.href = URL.createObjectURL(blob);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(link.href);
                }, 'image/png', 1.0); // Use 1.0 for maximum quality
            }).catch(function(error) {
                console.error('Error generating QR card:', error);
                alert('Error generating QR card. Please try again.');
            });
        }
    <?php endif; ?>


    // --- Autocomplete Dropdown for Registered Students ---
    const searchInput = document.getElementById('student-search');
    const autocompleteList = document.getElementById('autocomplete-list');
    const selectedStudentDiv = document.getElementById('selected-student');
    let searchTimeout = null;
    let currentResults = [];
    let selectedIndex = -1;

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        clearTimeout(searchTimeout);
        autocompleteList.innerHTML = '';
        autocompleteList.classList.add('hidden');
        selectedStudentDiv.innerHTML = '';
        selectedIndex = -1;
        if (!query) return;
        searchTimeout = setTimeout(() => {
            fetch('search_students.php?q=' + encodeURIComponent(query))
                .then(res => res.json())
                .then(data => {
                    currentResults = data;
                    if (data.length === 0) {
                        autocompleteList.innerHTML = '<div class="px-4 py-2 text-gray-500">No students found.</div>';
                        autocompleteList.classList.remove('hidden');
                        return;
                    }
                    autocompleteList.innerHTML = '';
                    data.forEach((student, idx) => {
                        const option = document.createElement('div');
                        option.className = 'px-4 py-2 cursor-pointer hover:bg-blue-100 flex flex-col';
                        option.innerHTML = `<span class="font-semibold text-gray-800">${student.name}</span><span class="text-xs text-gray-500">ID: ${student.student_id}</span>`;
                        option.addEventListener('mousedown', function(e) {
                            e.preventDefault();
                            showSelectedStudent(student);
                            autocompleteList.classList.add('hidden');
                            searchInput.value = student.name;
                        });
                        autocompleteList.appendChild(option);
                    });
                    autocompleteList.classList.remove('hidden');
                });
        }, 200);
    });

    // Keyboard navigation for dropdown
    searchInput.addEventListener('keydown', function(e) {
        if (!currentResults.length || autocompleteList.classList.contains('hidden')) return;
        if (e.key === 'ArrowDown') {
            selectedIndex = (selectedIndex + 1) % currentResults.length;
            updateActiveOption();
            e.preventDefault();
        } else if (e.key === 'ArrowUp') {
            selectedIndex = (selectedIndex - 1 + currentResults.length) % currentResults.length;
            updateActiveOption();
            e.preventDefault();
        } else if (e.key === 'Enter') {
            if (selectedIndex >= 0 && selectedIndex < currentResults.length) {
                showSelectedStudent(currentResults[selectedIndex]);
                autocompleteList.classList.add('hidden');
                searchInput.value = currentResults[selectedIndex].name;
                e.preventDefault();
            }
        }
    });

    function updateActiveOption() {
        const options = autocompleteList.querySelectorAll('div');
        options.forEach((opt, idx) => {
            if (idx === selectedIndex) {
                opt.classList.add('bg-blue-100');
            } else {
                opt.classList.remove('bg-blue-100');
            }
        });
    }

    function showSelectedStudent(student) {
        selectedStudentDiv.innerHTML = `
            <div class="flex items-center justify-between bg-blue-50 rounded-lg px-4 py-3 shadow-sm mt-2">
                <div>
                    <div class="font-semibold text-gray-800">${student.name}</div>
                    <div class="text-xs text-gray-500">ID: ${student.student_id}</div>
                </div>
                <button class="download-qr-btn bg-green-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-green-700 transition" data-student-id="${student.student_id}" data-student-name="${student.name}">
                    <i class="fas fa-download"></i> Download QR
                </button>
            </div>
        `;
        // Attach download logic
        selectedStudentDiv.querySelector('.download-qr-btn').addEventListener('click', async function() {
            const studentId = this.getAttribute('data-student-id');
            const studentName = this.getAttribute('data-student-name');
            // Create a temporary container to render the QR card design
            const tempContainer = document.createElement('div');
            tempContainer.style.position = 'absolute';
            tempContainer.style.left = '-9999px'; // Position off-screen
            tempContainer.style.top = '-9999px';
            tempContainer.className = 'qr-container mx-auto'; // Use the same styles and add mx-auto

             tempContainer.innerHTML = `
                <div class="qr-header">
                    <img src="evsulogo/evsu-logo.jpg" alt="EVSU Logo" class="evsu-logo">
                    <div class="qr-title">EASTERN VISAYAS STATE UNIVERSITY-OCC</div>
                    <div class="qr-subtitle">COMPUTER STUDIES DEPARTMENT</div>
                </div>
                <div class="qr-border">
                    <div id="temp-qrcode" style="width:300px; height:300px;"></div>
                </div>
                <div class="student-info text-center mt-4">
                    <div class="student-id-text font-bold text-lg text-gray-800">ID: ${studentId}</div>
                    <div class="student-name-text text-gray-700">Name: ${studentName}</div>
                </div>
             `;
             document.body.appendChild(tempContainer);

             // Generate QR code in the temporary container
            new QRCode(document.getElementById("temp-qrcode"), {
                text: studentId,
                width: 300,
                height: 300,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H,
                margin: 4,
                quietZone: 15
            });


            html2canvas(tempContainer, {
                scale: 3,
                backgroundColor: '#ffffff',
                logging: false,
                useCORS: true,
                allowTaint: true,
                imageTimeout: 15000,
                removeContainer: false,
                imageSmoothingEnabled: false,
                letterRendering: true
            }).then(canvas => {
                 const ctx = canvas.getContext('2d');

                // Calculate text positions below the QR code
                const textX = canvas.width / 2;
                // Calculate vertical position below the QR code and border within the captured element
                const qrContainerHeight = tempContainer.offsetHeight;
                const textY = qrContainerHeight * 3 + 30; // Position text 30px below the captured element (scaled)

                ctx.font = 'bold 30px sans-serif'; // Adjust font size and style
                ctx.fillStyle = '#000000'; // Text color
                ctx.textAlign = 'center';
                ctx.fillText("ID: " + studentId, textX, textY);

                ctx.font = '25px sans-serif'; // Adjust font size and style for name
                ctx.fillStyle = '#000000'; // Text color
                ctx.textAlign = 'center';
                ctx.fillText("Name: " + studentName, textX, textY + 40); // Adjust vertical spacing

                canvas.toBlob(function(blob) {
                    const link = document.createElement('a');
                    link.download = `EVSU-QRCard-${studentId}.png`;
                    link.href = URL.createObjectURL(blob);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(link.href);
                    // Remove temporary container
                    document.body.removeChild(tempContainer);
                }, 'image/png', 1.0);
            }).catch(function(error) {
                 console.error('Error generating QR card:', error);
                 alert('Error generating QR card. Please try again.');
                 // Ensure temporary container is removed even on error
                 if(document.body.contains(tempContainer)) {
                     document.body.removeChild(tempContainer);
                 }
            });
        });
    }

    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!autocompleteList.contains(e.target) && e.target !== searchInput) {
            autocompleteList.classList.add('hidden');
        }
    });
    </script>
</body>
</html> 