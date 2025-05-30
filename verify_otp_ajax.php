<?php
session_start();
header('Content-Type: application/json');
$email = $_POST['email'] ?? '';
$otp = $_POST['otp'] ?? '';
if (!$email || !$otp) {
    echo json_encode(['success' => false, 'message' => 'Email and code required.']);
    exit;
}
$session = $_SESSION['email_verification'] ?? null;
if (!$session || $session['email'] !== $email) {
    echo json_encode(['success' => false, 'message' => 'No verification code sent for this email.']);
    exit;
}
if (time() > $session['expires_at']) {
    echo json_encode(['success' => false, 'message' => 'Verification code expired.']);
    exit;
}
if ($session['otp'] === $otp) {
    echo json_encode(['success' => true, 'message' => 'Email verified!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid code.']);
} 