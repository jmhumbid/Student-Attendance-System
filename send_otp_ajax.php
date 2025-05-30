<?php
require_once 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
session_start();
header('Content-Type: application/json');
$email = $_POST['email'] ?? '';
if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email required.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}
// Generate a 6-digit code
$otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$_SESSION['email_verification'] = [
    'email' => $email,
    'otp' => $otp,
    'expires_at' => time() + 15 * 60 // 15 minutes
];
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'kuraidiner@gmail.com';
    $mail->Password = 'kixukurpwkskeomv';
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;
    $mail->setFrom('kuraidiner@gmail.com', 'QR Lab System');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'QR Lab - Email Verification Code';
    $mail->Body = "<div style='font-family:Inter,sans-serif;background:#f3f4f6;padding:32px;max-width:600px;margin:auto;border-radius:16px;box-shadow:0 8px 32px 0 rgba(31,38,135,0.18);'><div style='text-align:center;margin-bottom:24px;'><div style='display:inline-block;background:linear-gradient(90deg,#6366f1,#a21caf,#ec4899);padding:16px;border-radius:50%;margin-bottom:16px;'><img src='https://cdn-icons-png.flaticon.com/512/561/561127.png' width='48' style='vertical-align:middle;'></div></div><h2 style='color:#1e293b;font-size:24px;margin-bottom:16px;'>Email Verification</h2><p style='color:#334155;font-size:16px;margin-bottom:24px;'>Your verification code is:</p><div style='font-size:32px;font-weight:bold;letter-spacing:8px;background:#fff;padding:16px 0;border-radius:8px;box-shadow:0 2px 8px 0 rgba(31,38,135,0.08);margin-bottom:24px;'>$otp</div><p style='color:#64748b;font-size:14px;'>This code will expire in 15 minutes.</p></div>";
    $mail->AltBody = "Your QR Lab email verification code is: $otp\nThis code will expire in 15 minutes.";
    $mail->send();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to send verification code.']);
} 