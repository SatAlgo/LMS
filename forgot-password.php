<?php
// 1. Debugging - REMOVE THESE 3 LINES ONCE IT WORKS
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "includes/config.php"; 

// 2. CHECK PATHS: If these are wrong, you get a blank page.
require 'includes/PHPMailer/Exception.php';
require 'includes/PHPMailer/PHPMailer.php';
require 'includes/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = ""; 
$message_type = "";

if (isset($_POST['send_otp'])) {
    // 3. Clean Input
    $email = mysqli_real_escape_string($connection, trim(strtolower($_POST['email'])));
    $otp = rand(100000, 999999);
    $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

    // 4. Update Database First
    mysqli_query($connection, "UPDATE staff_accounts SET reset_otp='$otp', otp_expiry='$expiry' WHERE LOWER(email)='$email'");
    $staff_up = mysqli_affected_rows($connection);

    mysqli_query($connection, "UPDATE users SET reset_otp='$otp', otp_expiry='$expiry' WHERE LOWER(email)='$email'");
    $user_up = mysqli_affected_rows($connection);

    if ($staff_up > 0 || $user_up > 0) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'algorithmseven@gmail.com'; 
            $mail->Password = 'rafj opvi ncmb hnru'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('algorithmseven@gmail.com', 'MIT Library Security');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Recovery OTP';
            $mail->Body = "Your OTP is: <b>$otp</b>. Valid for 10 minutes.";

            if($mail->send()) {
                $_SESSION['reset_email'] = $email;
                header("Location: reset-password.php"); 
                exit();
            }
        } catch (Exception $e) {
            $message = "Mail Error: " . $mail->ErrorInfo;
            $message_type = "danger";
        }
    } else {
        $message = "Email not found in our records.";
        $message_type = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | MIT Library</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; height: 100vh; display: flex; align-items: center; }
        .forgot-card { max-width: 400px; width: 100%; margin: auto; border-radius: 15px; border: none; }
        .btn-primary { background: #141D49; border: none; padding: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card forgot-card p-4 shadow-lg">
            <div class="text-center mb-4">
                <i class="fas fa-lock-open fa-3x text-primary mb-2"></i>
                <h3 class="font-weight-bold">Forgot Password</h3>
                <p class="text-muted small">Enter your email for the recovery code</p>
            </div>

            <?php if($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> small text-center"><?php echo $message; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label class="small font-weight-bold">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
                </div>
                <button type="submit" name="send_otp" class="btn btn-primary btn-block font-weight-bold mt-3">SEND OTP</button>
            </form>
            <div class="text-center mt-3">
                <a href="index.php" class="small text-muted font-weight-bold">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>