<?php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'attendance';

date_default_timezone_set('Asia/Manila');

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$conn->query("CREATE DATABASE IF NOT EXISTS `$DB_NAME`");
$conn->select_db($DB_NAME);
$conn->query("CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    gender VARCHAR(10) NOT NULL,
    year VARCHAR(20) NOT NULL,
    course VARCHAR(50) NOT NULL
)");
$conn->query("CREATE TABLE IF NOT EXISTS instructors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    instructor_id VARCHAR(50) UNIQUE NOT NULL,
    department VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    failed_login_attempts INT DEFAULT 0,
    lockout_until DATETIME DEFAULT NULL,
    otp VARCHAR(6) DEFAULT NULL,
    otp_expires_at DATETIME DEFAULT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    background_image VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE CASCADE
)");
$conn->query("CREATE TABLE IF NOT EXISTS class_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    student_id INT NOT NULL,
    UNIQUE KEY class_student_unique (class_id, student_id),
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
)");
$conn->query("CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    student_id INT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);");
$conn->query("CREATE TABLE IF NOT EXISTS user_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    user_type VARCHAR(20) NULL,
    action VARCHAR(50) NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    INDEX user_id_idx (user_id),
    INDEX action_idx (action)
);");
$conn->query("CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);");

// Insert or update the administrator account
$admin_username = 'administrator';
$admin_password_plain = 'skypianadmin';
$admin_password_hash = password_hash($admin_password_plain, PASSWORD_DEFAULT);

// Check if admin exists
$stmt = $conn->prepare("SELECT id FROM admin WHERE username = ?");
$stmt->bind_param('s', $admin_username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    // Update password if admin exists
    $stmt->close();
    $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE username = ?");
    $stmt->bind_param('ss', $admin_password_hash, $admin_username);
    $stmt->execute();
    $stmt->close();
} else {
    $stmt->close();
    $stmt = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
    $stmt->bind_param('ss', $admin_username, $admin_password_hash);
    $stmt->execute();
    $stmt->close();
}

function add_student($student_id, $name, $gender, $year, $course) {
    global $conn;
    $stmt = $conn->prepare('INSERT IGNORE INTO students (student_id, name, gender, year, course) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('sssss', $student_id, $name, $gender, $year, $course);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function get_students() {
    global $conn;
    $res = $conn->query('SELECT student_id, name, gender, year, course FROM students');
    $students = [];
    while ($row = $res->fetch_assoc()) {
        $students[] = $row;
    }
    return $students;
}

function mark_attendance($student_id) {
    global $conn;
    $stmt = $conn->prepare('INSERT INTO attendance (student_id) VALUES (?)');
    $stmt->bind_param('s', $student_id);
    $stmt->execute();
    $stmt->close();
}

function get_attendance_counts() {
    global $conn;
    $res = $conn->query('SELECT student_id, timestamp FROM attendance ORDER BY timestamp DESC');
    $attendance = [];
    while ($row = $res->fetch_assoc()) {
        if (!isset($attendance[$row['student_id']])) {
            $attendance[$row['student_id']] = [];
        }
        $attendance[$row['student_id']][] = $row['timestamp'];
    }
    return $attendance;
}

function add_instructor($full_name, $instructor_id, $department, $email, $username, $password) {
    global $conn;
    $encoded_password = base64_encode($password);
    $stmt = $conn->prepare('INSERT IGNORE INTO instructors (full_name, instructor_id, department, email, username, password) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssss', $full_name, $instructor_id, $department, $email, $username, $encoded_password);
    
    if ($stmt->execute()) {
        if ($conn->affected_rows > 0) {
            $stmt->close();
            return true;
        } elseif ($conn->affected_rows === 0) {
            $stmt->close();
            return 'Username or Instructor ID already exists.';
        } else {
            $stmt->close();
            return 'Database error: No rows affected unexpectedly.';
        }
    } else {
        $error_message = $conn->error;
        $stmt->close();
        return 'Database error during execution: ' . $error_message;
    }
}

function update_instructor_password($instructor_id, $new_password) {
    global $conn;
    $stmt = $conn->prepare('SELECT email FROM instructors WHERE id = ?');
    $stmt->bind_param('i', $instructor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $instructor = $result->fetch_assoc();
    $stmt->close();
    $encoded_password = base64_encode($new_password);
    $stmt = $conn->prepare('UPDATE instructors SET password = ? WHERE id = ?');
    $stmt->bind_param('si', $encoded_password, $instructor_id);
    if ($stmt->execute()) {
        $stmt->close();
        if ($instructor) {
            $subject = 'QR Lab - Password Changed Successfully';
            $message = "
                <p>Dear Instructor,</p>
                <p>Your QR Lab account password has been successfully changed.</p>
                <p>Details of the change:</p>
                <ul>
                    <li>Time: " . date('Y-m-d H:i:s') . "</li>
                    <li>Action: Password Change</li>
                </ul>
                <p>If you did not make this change, please contact the system administrator immediately.</p>
                <p>For security reasons, we recommend:</p>
                <ul>
                    <li>Keeping your password secure and not sharing it with anyone</li>
                    <li>Using a strong, unique password</li>
                    <li>Enabling two-factor authentication if not already enabled</li>
                </ul>
            ";
            send_security_notification($instructor['email'], $subject, $message);
        }
        return true;
    } else {
        $error_message = $conn->error;
        $stmt->close();
        return 'Database error: ' . $error_message;
    }
}

function get_instructor_by_username($username) {
    global $conn;
    $stmt = $conn->prepare('SELECT id, username, password, full_name, email, failed_login_attempts, lockout_until, last_login FROM instructors WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $instructor = $result->fetch_assoc();
    $stmt->close();
    return $instructor;
}

function get_instructor_by_email($email) {
    global $conn;
    $stmt = $conn->prepare('SELECT id, username, password, full_name, email, failed_login_attempts, lockout_until, last_login FROM instructors WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $instructor = $result->fetch_assoc();
    $stmt->close();
    return $instructor;
}

function get_total_students_count() {
    global $conn;
    $res = $conn->query('SELECT COUNT(*) AS total FROM students');
    $row = $res->fetch_assoc();
    return $row['total'] ?? 0;
}

function get_male_students_count() {
    global $conn;
    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM students WHERE gender = ?');
    $gender = 'Male';
    $stmt->bind_param('s', $gender);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row['total'] ?? 0;
}

function get_female_students_count() {
    global $conn;
    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM students WHERE gender = ?');
    $gender = 'Female';
    $stmt->bind_param('s', $gender);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row['total'] ?? 0;
}

function get_total_instructors_count() {
    global $conn;
    $res = $conn->query('SELECT COUNT(*) AS total FROM instructors');
    $row = $res->fetch_assoc();
    return $row['total'] ?? 0;
}

function add_class($instructor_id, $class_name, $subject, $start_time, $end_time, $background_image = null) {
    global $conn;
    $stmt = $conn->prepare('INSERT INTO classes (instructor_id, class_name, subject, start_time, end_time, background_image) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('isssss', $instructor_id, $class_name, $subject, $start_time, $end_time, $background_image);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function get_instructor_classes($instructor_id) {
    global $conn;
    $stmt = $conn->prepare('SELECT id, class_name, subject, start_time, end_time, background_image FROM classes WHERE instructor_id = ?');
    $stmt->bind_param('i', $instructor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $classes = [];
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
    $stmt->close();
    return $classes;
}

function get_all_students() {
    global $conn;
    $res = $conn->query('SELECT id, student_id, name FROM students ORDER BY name');
    $students = [];
    while ($row = $res->fetch_assoc()) {
        $students[] = $row;
    }
    return $students;
}

function enroll_student_in_class($class_id, $student_id) {
    global $conn;
    $stmt = $conn->prepare('INSERT IGNORE INTO class_students (class_id, student_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $class_id, $student_id);
    
    if ($stmt->execute()) {
        if ($conn->affected_rows > 0) {
            $stmt->close();
            return true;
        } elseif ($conn->affected_rows === 0) {
            $stmt->close();
            return 'duplicate';
        } else {
            $stmt->close();
            return 'Database error: No rows affected unexpectedly.';
        }
    } else {
        $error_message = $conn->error;
        $stmt->close();
        return 'Database error during execution: ' . $error_message;
    }
}

function get_enrolled_students($class_id) {
    global $conn;
    $stmt = $conn->prepare('SELECT s.id, s.student_id, s.name, s.gender, s.year, s.course FROM students s JOIN class_students cs ON s.id = cs.student_id WHERE cs.class_id = ? ORDER BY s.name');
    $stmt->bind_param('i', $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
    return $students;
}

function record_attendance($class_id, $student_id) {
    global $conn;
    $current_time = date('Y-m-d H:i:s');
    $stmt = $conn->prepare('INSERT INTO attendance (class_id, student_id, timestamp) VALUES (?, ?, ?)');
    $stmt->bind_param('iis', $class_id, $student_id, $current_time);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function get_class_attendance($class_id) {
    global $conn;
    $stmt = $conn->prepare('SELECT student_id, timestamp FROM attendance WHERE class_id = ? ORDER BY timestamp DESC');
    $stmt->bind_param('i', $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $attendance = [];
    while ($row = $result->fetch_assoc()) {
        if (!isset($attendance[$row['student_id']])) {
            $attendance[$row['student_id']] = [];
        }
        $attendance[$row['student_id']][] = $row['timestamp'];
    }
    $stmt->close();
    return $attendance;
}

function record_failed_login_attempt($instructor_id) {
    global $conn;
    $lockout_threshold = 3;
    $lockout_duration = 300;
    $current_time = date('Y-m-d H:i:s');

    $stmt = $conn->prepare('SELECT email, failed_login_attempts FROM instructors WHERE id = ?');
    $stmt->bind_param('i', $instructor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $instructor = $result->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare('UPDATE instructors SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?');
    $stmt->bind_param('i', $instructor_id);
    $stmt->execute();
    $stmt->close();

    if ($instructor && $instructor['failed_login_attempts'] + 1 >= $lockout_threshold) {
        $lockout_time = date('Y-m-d H:i:s', time() + $lockout_duration);
        $stmt = $conn->prepare('UPDATE instructors SET lockout_until = ? WHERE id = ?');
        $stmt->bind_param('si', $lockout_time, $instructor_id);
        $stmt->execute();
        $stmt->close();

        $subject = 'QR Lab - Account Locked Due to Multiple Failed Login Attempts';
        $message = "
            <p>Dear Instructor,</p>
            
            <p class='warning'>We have detected multiple failed login attempts on your QR Lab account.</p>
            
            <p>For security reasons, your account has been temporarily locked for 5 minutes.</p>
            
            <p>Details of the incident:</p>
            <ul>
                <li>Time: {$current_time}</li>
                <li>Failed Attempts: {$lockout_threshold}</li>
                <li>Lockout Duration: 5 minutes</li>
            </ul>
            
            <p>If this was you, please wait for the lockout period to end before trying again.</p>
            
            <p>If you did not attempt to log in, please contact the system administrator immediately.</p>
            
            <p>For security reasons, we recommend:</p>
            <ul>
                <li>Changing your password if you believe it has been compromised</li>
                <li>Enabling two-factor authentication if not already enabled</li>
                <li>Reviewing your account activity for any suspicious behavior</li>
            </ul>
        ";
        
        send_security_notification($instructor['email'], $subject, $message);
    }
}

function reset_failed_login_attempts($instructor_id) {
    global $conn;
    $current_time = date('Y-m-d H:i:s');
    $stmt = $conn->prepare('UPDATE instructors SET failed_login_attempts = 0, lockout_until = NULL, last_login = ? WHERE id = ?');
    $stmt->bind_param('si', $current_time, $instructor_id);
    $stmt->execute();
    $stmt->close();
}

function get_all_instructors() {
    global $conn;
    $res = $conn->query('SELECT id, full_name, instructor_id, department, email, username, failed_login_attempts, lockout_until, last_login FROM instructors');
    $instructors = [];
    while ($row = $res->fetch_assoc()) {
        $instructors[] = $row;
    }
    return $instructors;
}

function add_log_entry($user_id, $user_type, $action, $ip_address = NULL, $user_agent = NULL) {
    global $conn;
    $stmt = $conn->prepare('INSERT INTO user_logs (user_id, user_type, action, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('issss', $user_id, $user_type, $action, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}

function get_recent_log_entries($limit = 20) {
    global $conn;
    $stmt = $conn->prepare('SELECT ul.*, i.username as instructor_username FROM user_logs ul LEFT JOIN instructors i ON ul.user_id = i.id AND ul.user_type = \'instructor\' ORDER BY ul.timestamp DESC LIMIT ?');
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    $stmt->close();
    return $logs;
}

function generate_and_send_otp($instructor_id, $email) {
    global $conn;
    
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    $stmt = $conn->prepare("UPDATE instructors SET otp = ?, otp_expires_at = ? WHERE id = ?");
    $stmt->bind_param("ssi", $otp, $expires_at, $instructor_id);
    
    if (!$stmt->execute()) {
        return false;
    }
    
    require_once 'phpmailer/src/Exception.php';
    require_once 'phpmailer/src/PHPMailer.php';
    require_once 'phpmailer/src/SMTP.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
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
        $mail->Subject = 'QR Lab - Login Verification Code';
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { text-align: center; padding: 20px 0; background-color: #4CAF50; color: white; border-radius: 5px 5px 0 0; }
                    .content { padding: 30px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 0 0 5px 5px; }
                    .otp-box { background-color: #fff; padding: 20px; border: 2px dashed #4CAF50; border-radius: 5px; text-align: center; margin: 20px 0; }
                    .otp-code { font-size: 32px; font-weight: bold; color: #4CAF50; letter-spacing: 5px; }
                    .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
                    .warning { color: #ff4444; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>QR Lab Login Verification</h1>
                    </div>
                    <div class='content'>
                        <p>Dear Instructor,</p>
                        
                        <p>We received a login attempt to your QR Lab account. To ensure the security of your account, please use the following verification code to complete your login:</p>
                        
                        <div class='otp-box'>
                            <div class='otp-code'>{$otp}</div>
                        </div>
                        
                        <p>This verification code will expire in <strong>15 minutes</strong>.</p>
                        
                        <p class='warning'>If you did not attempt to log in to your account, please ignore this email and ensure your account password is secure.</p>
                        
                        <p>For security reasons, please do not share this code with anyone.</p>
                        
                        <div class='footer'>
                            <p>This is an automated message, please do not reply to this email.</p>
                            <p>© " . date('Y') . " QR Lab System. All rights reserved.</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->AltBody = "
            QR Lab Login Verification
            
            Dear Instructor,
            
            We received a login attempt to your QR Lab account. To ensure the security of your account, please use the following verification code to complete your login:
            
            {$otp}
            
            This verification code will expire in 15 minutes.
            
            If you did not attempt to log in to your account, please ignore this email and ensure your account password is secure.
            
            For security reasons, please do not share this code with anyone.
            
            This is an automated message, please do not reply to this email.
            © " . date('Y') . " QR Lab System. All rights reserved.
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function verify_otp($instructor_id, $otp) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT otp, otp_expires_at FROM instructors WHERE id = ?");
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if ($row['otp'] === $otp && strtotime($row['otp_expires_at']) > time()) {
            $stmt = $conn->prepare("UPDATE instructors SET otp = NULL, otp_expires_at = NULL, is_verified = TRUE WHERE id = ?");
            $stmt->bind_param("i", $instructor_id);
            $stmt->execute();
            return true;
        }
    }
    return false;
}

function send_security_notification($email, $subject, $message) {
    require_once 'phpmailer/src/Exception.php';
    require_once 'phpmailer/src/PHPMailer.php';
    require_once 'phpmailer/src/SMTP.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'kuraidiner@gmail.com';
        $mail->Password = 'kixukurpwkskeomv';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        
        $mail->setFrom('kuraidiner@gmail.com', 'QR Lab Security');
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { text-align: center; padding: 20px 0; background-color: #dc3545; color: white; border-radius: 5px 5px 0 0; }
                    .content { padding: 30px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 0 0 5px 5px; }
                    .warning { color: #dc3545; font-weight: bold; }
                    .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
                    .button { display: inline-block; padding: 10px 20px; background-color: #dc3545; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Security Alert</h1>
                    </div>
                    <div class='content'>
                        {$message}
                        <div class='footer'>
                            <p>This is an automated security message, please do not reply to this email.</p>
                            <p>© " . date('Y') . " QR Lab System. All rights reserved.</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function delete_class($class_id, $instructor_id) {
    global $conn;
    $stmt = $conn->prepare('DELETE FROM classes WHERE id = ? AND instructor_id = ?');
    $stmt->bind_param('ii', $class_id, $instructor_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}
?>