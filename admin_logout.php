<?php
session_start();

require_once __DIR__ . '/db.php';

// Log the admin logout action
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    add_log_entry(NULL, 'admin', 'Logout', $_SERVER['REMOTE_ADDR'] ?? NULL, $_SERVER['HTTP_USER_AGENT'] ?? NULL);
}

// Unset all session variables
$_SESSION = array();

session_destroy();

// Redirect to the admin login page
header('Location: admin_login.php');
exit();
 