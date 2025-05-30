<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['instructor_id']) || !isset($_SESSION['pending_verification'])) {
    header('Location: instructor_login.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'] ?? '';
    
    if (empty($otp)) {
        $error = 'Please enter the verification code.';
    } else {
        if (verify_otp($_SESSION['instructor_id'], $otp)) {
            $_SESSION['pending_verification'] = false;
            $_SESSION['is_verified'] = true;
            header('Location: instructor_dashboard.php');
            exit();
        } else {
            $error = 'Invalid or expired verification code.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Login - QR Lab</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .verification-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .otp-input {
            letter-spacing: 8px;
            font-size: 24px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-container">
            <h2 class="text-center mb-4">Verify Your Login</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="otp">Enter Verification Code</label>
                    <input type="text" class="form-control otp-input" id="otp" name="otp" maxlength="6" required>
                    <small class="form-text text-muted">Please enter the 6-digit code sent to your email.</small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Verify</button>
            </form>
            
            <div class="text-center mt-3">
                <a href="instructor_login.php">Back to Login</a>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 