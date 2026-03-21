<?php
session_start();
require_once "includes/config.php";

if (!isset($_SESSION['reset_email'])) {
    header("Location: index.php");
    exit();
}

$message = ""; $message_type = "";
$email = $_SESSION['reset_email'];
$typed_otp = "";

if (isset($_POST['reset_now'])) {
    $typed_otp = mysqli_real_escape_string($connection, trim($_POST['otp']));
    $new_pass = $_POST['new_password'];
    $conf_pass = $_POST['confirm_password'];

    if ($new_pass !== $conf_pass) {
        $message = "Passwords do not match!"; $message_type = "danger";
    } elseif (strlen($new_pass) < 5) {
        $message = "Password must be at least 5 characters."; $message_type = "danger";
    } else {
        // Verify from DB
        $check_staff = mysqli_query($connection, "SELECT id FROM staff_accounts WHERE email='$email' AND reset_otp='$typed_otp' AND otp_expiry > NOW()");
        $check_user = mysqli_query($connection, "SELECT id FROM users WHERE email='$email' AND reset_otp='$typed_otp' AND otp_expiry > NOW()");

        if (mysqli_num_rows($check_staff) > 0 || mysqli_num_rows($check_user) > 0) {
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);

            mysqli_query($connection, "UPDATE staff_accounts SET password='$hashed_pass', reset_otp=NULL, otp_expiry=NULL WHERE email='$email'");
            mysqli_query($connection, "UPDATE users SET password='$hashed_pass', reset_otp=NULL, otp_expiry=NULL WHERE email='$email'");

            unset($_SESSION['reset_email']);
            header("Location: index.php?msg=reset_success");
            exit();
        } else {
            $message = "Invalid or expired OTP. Please check again."; $message_type = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | MIT Library</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f7f6; height: 100vh; display: flex; align-items: center; }
        .reset-card { max-width: 400px; width: 100%; margin: auto; border-radius: 15px; border: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card reset-card p-4 shadow-lg">
            <h4 class="font-weight-bold text-center mb-3">Set New Password</h4>
            
            <?php if($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> small text-center"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="small font-weight-bold">Enter 6-Digit OTP</label>
                    <input type="text" name="otp" class="form-control text-center font-weight-bold" maxlength="6" value="<?php echo $typed_otp; ?>" required>
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold">New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" name="reset_now" class="btn btn-success btn-block font-weight-bold mt-3">UPDATE PASSWORD</button>
            </form>
        </div>
    </div>
</body>
</html>