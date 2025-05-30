<?php
// scan.php - Public QR Code Tester for Students
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Tester</title>
    <script>
      tailwind.config = {
        darkMode: 'class'
      }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 min-h-screen flex items-center justify-center transition-colors duration-300">
    <!-- Back Button -->
    <div class="absolute top-6 left-6 z-20">
        <a href="index.php" class="inline-flex items-center px-4 py-2 bg-gray-800 text-gray-100 font-semibold rounded-lg shadow transition duration-200 border border-gray-700 hover:bg-gray-700">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>
    <div class="w-full max-w-md mx-auto p-6 md:p-10 rounded-3xl shadow-2xl glass-card bg-gray-900/80 relative flex flex-col items-center shadow-gray-900">
        <h2 class="text-3xl font-extrabold text-gray-100 mb-2 tracking-tight text-center">QR Code Tester</h2>
        <p class="text-gray-300 text-center mb-6">Scan your QR code below to check if it is scannable.<br>No login required.</p>
        <div class="w-full flex flex-col items-center">
            <div class="border-4 border-gray-700 rounded-2xl bg-gray-800 shadow-lg p-2 mb-6 w-full flex justify-center min-h-[320px] shadow-gray-900">
                <div id="reader" class="w-full max-w-xs mx-auto"></div>
            </div>
            <div id="qr-result" class="hidden w-full bg-green-900 border border-green-700 rounded-xl p-5 mt-2 flex flex-col items-center justify-center text-green-200 text-lg font-semibold shadow">
                <i class="fas fa-check-circle text-3xl text-green-300 mb-2"></i>
                <span id="qr-value"></span>
            </div>
        </div>
    </div>
    <script>
        // QR Scanner logic
        let html5QrcodeScanner = new Html5Qrcode("reader");
        let scanning = false;
        function onScanSuccess(decodedText, decodedResult) {
            const resultBox = document.getElementById('qr-result');
            const valueSpan = document.getElementById('qr-value');
            resultBox.classList.remove('hidden');
            valueSpan.textContent = decodedText;
            // Optionally, stop scanning after a successful scan
            if (scanning) {
                html5QrcodeScanner.stop().then(() => { scanning = false; });
            }
        }
        function onScanFailure(error) {
            // Optionally show scan errors
        }
        scanning = true;
        html5QrcodeScanner.start(
            { facingMode: "environment" },
            {
                fps: 10,
                qrbox: { width: 250, height: 250 }
            },
            onScanSuccess,
            onScanFailure
        );
    </script>
</body>
</html>
