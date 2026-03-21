<?php
session_start();
require_once "../includes/config.php";
require '../includes/PHPMailer/Exception.php';
require '../includes/PHPMailer/PHPMailer.php';
require '../includes/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Role-Based Access Control
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$message = "";
$message_type = "";
$prn = $_SESSION['prn'];
$email = ""; // Will be fetched from DB

// Fetch student email
$user_res = mysqli_query($connection, "SELECT email FROM users WHERE prn = '$prn'");
if($user = mysqli_fetch_assoc($user_res)) {
    $email = $user['email'];
}

// STEP 1: Send OTP
if (isset($_POST['send_otp'])) {
    $otp = rand(100000, 999999);
    $_SESSION['temp_otp'] = $otp;
    $_SESSION['otp_time'] = time();

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'algorithmseven@gmail.com'; // Your Email
        $mail->Password   = 'rafj opvi ncmb hnru';       // Your App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('algorithmseven@gmail.com', 'MIT Library OTP');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'LMS Password Reset OTP';
        $mail->Body    = "Your OTP for changing your library password is: <b>$otp</b>. This is valid for 10 minutes.";

        $mail->send();
        $message = "OTP has been sent to your registered email ($email).";
        $message_type = "success";
    } catch (Exception $e) {
        $message = "Error sending email. Please check your internet connection.";
        $message_type = "danger";
    }
}

// STEP 2: Verify OTP and Change Password
if (isset($_POST['update_password'])) {
    $user_otp = $_POST['otp'];
    $new_pass = $_POST['new_password'];
    $conf_pass = $_POST['confirm_password'];

    if ($user_otp != $_SESSION['temp_otp']) {
        $message = "Invalid OTP. Please try again.";
        $message_type = "danger";
    } elseif ($new_pass !== $conf_pass) {
        $message = "Passwords do not match.";
        $message_type = "danger";
    } else {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT); // Secure hashing
        $update = "UPDATE users SET password = '$hashed_pass' WHERE prn = '$prn'";
        
        if (mysqli_query($connection, $update)) {
            unset($_SESSION['temp_otp']);
            $message = "Password updated successfully!";
            $message_type = "success";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password | LMS</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f7f6; padding-top: 50px; }
        .card { max-width: 450px; margin: auto; border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="container">
    <div class="card p-4">
        <h4 class="text-center font-weight-bold mb-4">Security Settings</h4>
        
        <?php if($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> small text-center"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="small font-weight-bold">Registered Email</label>
                <input type="text" class="form-control" value="<?php echo $email; ?>" disabled>
            </div>

            <?php if(!isset($_SESSION['temp_otp'])): ?>
                <button type="submit" name="send_otp" class="btn btn-dark btn-block">Send Verification OTP</button>
            <?php else: ?>
                <div class="form-group mt-3">
                    <label class="small font-weight-bold">Enter 6-Digit OTP</label>
                    <input type="text" name="otp" class="form-control" placeholder="XXXXXX" required>
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold">New Password</label>
                    <input type="password" name="new_password" class="form-control" minlength="8" required>
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" name="update_password" class="btn btn-success btn-block">Update Password</button>
                <div class="text-center mt-2">
                    <a href="change-password.php" class="small text-muted">Resend OTP?</a>
                </div>
            <?php endif; ?>
        </form>
        <div class="text-center mt-3">
            <a href="dashboard.php" class="small">Back to Dashboard</a>
        </div>
    </div>
</div>

<script src="../assets/js/jquery.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>