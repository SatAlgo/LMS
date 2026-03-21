<?php
// Database credentials
$db_host = "localhost";
$db_user = "root";       
$db_pass = "";           
$db_name = "lms";       

// 1. Set PHP Timezone first
date_default_timezone_set('Asia/Kolkata');

// 2. Create connection FIRST
$connection = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// 3. Check connection
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// 4. NOW set MySQL Timezone and Charset using the established connection
mysqli_query($connection, "SET time_zone = '+05:30'");
mysqli_set_charset($connection, "utf8mb4");

define('BASE_URL', 'http://localhost/LMS/');

// Helper function for Email Toggles
function isEmailEnabled($connection, $config_key) {
    $res = mysqli_query($connection, "SELECT config_value FROM settings WHERE config_key = '$config_key' LIMIT 1");
    $row = mysqli_fetch_assoc($res);
    return (isset($row['config_value']) && $row['config_value'] == '1');
}

// Helper function to check if email exists anywhere in the system
function isEmailGlobalUnique($connection, $email) {
    $email = mysqli_real_escape_string($connection, trim(strtolower($email)));
    
    // Check staff table
    $staff = mysqli_query($connection, "SELECT id FROM staff_accounts WHERE LOWER(email) = '$email'");
    // Check users table
    $user = mysqli_query($connection, "SELECT id FROM users WHERE LOWER(email) = '$email'");
    
    // Returns true if email is found in either table
    return (mysqli_num_rows($staff) > 0 || mysqli_num_rows($user) > 0);
}

// Session Timeout Logic (30 Minutes)
$timeout_duration = 1800;

if (isset($_SESSION['last_activity'])) {
    $elapsed_time = time() - $_SESSION['last_activity'];
    
    if ($elapsed_time > $timeout_duration) {
        session_unset();
        session_destroy();
        header("Location: " . BASE_URL . "index.php?reason=timeout");
        exit();
    }
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>