<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['instructor_id'])) {
    add_log_entry($_SESSION['instructor_id'], 'instructor', 'logout', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
}

session_destroy();
header('Location: instructor_login.php');
exit();
?> 