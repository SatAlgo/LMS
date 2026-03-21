<?php
session_start();

// 1. Check for timeout reason first
if (isset($_GET['reason']) && $_GET['reason'] === 'timeout') {
    $error = "Your session expired due to inactivity. Please log in again.";
} else {
    $error = ""; // Initialize empty if no timeout occurred
}

// If a session already exists, redirect them to their dashboard automatically
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } elseif ($_SESSION['role'] === 'staff') {
        header("Location: staff/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit();
}

require_once "includes/config.php";

$error = "";
$typed_email = ""; // Variable to hold the typed email

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($connection, trim($_POST['email']));
    $typed_email = $email; // Save it to echo back in the input field
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // 1. Check Staff/Admin Accounts First
        $stmt = mysqli_prepare($connection, "SELECT id, name, email, password, role FROM staff_accounts WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $row['password']) || $password === $row['password']) {
                $_SESSION['id'] = $row['id'];
                $_SESSION['username'] = $row['name'];
                $_SESSION['role'] = $row['role'];
                
                // REDIRECTION LOGIC FIXED
                if ($row['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: staff/dashboard.php");
                }
                exit();
            } else { $error = "Invalid password."; }
        } else {
            // 2. Check Student Accounts
            $stmt2 = mysqli_prepare($connection, "SELECT id, name, prn, password FROM users WHERE email = ? OR prn = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt2, "ss", $email, $email);
            mysqli_stmt_execute($stmt2);
            $result2 = mysqli_stmt_get_result($stmt2);

            if ($user = mysqli_fetch_assoc($result2)) {
                if (password_verify($password, $user['password']) || $password === $user['password']) {
                    $_SESSION['id'] = $user['id'];
                    $_SESSION['username'] = $user['name'];
                    $_SESSION['prn'] = $user['prn'];
                    $_SESSION['role'] = 'student';
                    
                    header("Location: user/dashboard.php");
                    exit();
                } else { $error = "Invalid password."; }
            } else { $error = "User not found."; }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LMS | Login Portal</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; height: 100vh; display: flex; align-items: center; }
        .login-card { max-width: 400px; width: 100%; margin: auto; border-radius: 15px; border: none; }
        .btn-primary { background: #141D49; border: none; padding: 12px; }
        .input-group-text { background: white; cursor: pointer; border-left: none; }
        .form-control:focus { box-shadow: none; border-color: #ced4da; }
        .password-field { border-right: none; }
    </style>
</head>
<body>

<div class="container">
    <div class="card login-card p-4 shadow-lg">
        <!-- <div class="text-center mb-4">
            <div class="mb-2"><i class="fas fa-university fa-3x text-primary"></i></div>
            <h3 class="font-weight-bold">MIT AOE Library</h3>
            <p class="text-muted small">Enter your credentials to access the portal</p>
        </div> -->

        <div class="text-center mb-1">
            <div class="mb-1">
                <img src="assets/images/logo.jpg" alt="MIT AOE Logo" style="max-width: 250px; height: auto;">
            </div>
            <h4 class="font-weight-bold">Library Management System</h4>
            <p class="text-muted small">Enter your credentials to access the portal</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger small text-center py-2"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'reset_success'): ?>
            <div class="alert alert-success small text-center py-2 border-0 shadow-sm">
                <i class="fas fa-check-circle mr-1"></i> Password reset successful! Please login.
            </div>
        <?php endif; ?>

        <form action="" method="POST" autocomplete="off">
            <div class="form-group">
                <label class="small font-weight-bold">Email or PRN</label>
                <input type="text" name="email" class="form-control" placeholder="e.g. 202301040137" value="<?php echo htmlspecialchars($typed_email); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="small font-weight-bold">Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control password-field" placeholder="••••••••" required>
                    <div class="input-group-append">
                        <span class="input-group-text" id="togglePassword">
                            <i class="fas fa-eye text-muted"></i>
                        </span>
                    </div>
                </div>
            </div>

            <button type="submit" name="login" class="btn btn-primary btn-block font-weight-bold mt-4 shadow-sm">LOG IN</button>
            <div class="text-center mt-3">
                <a href="forgot-password.php" class="small text-muted font-weight-bold">Forgot Password?</a>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<!-- <script>
$(document).ready(function() {
    // Password Toggle Logic
    $('#togglePassword').click(function() {
        const passwordField = $('#password');
        const icon = $(this).find('i');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
});
</script> -->
<script>
$(document).ready(function() {
    // 1. Password Toggle Logic
    $('#togglePassword').click(function() {
        const passwordField = $('#password');
        const icon = $(this).find('i');
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // 2. Auto-Hide Notifications (3 seconds)
    if ($(".auto-hide").length > 0) {
        setTimeout(function() {
            $(".auto-hide").fadeOut("slow", function() {
                $(this).remove();
            });
        }, 3000);
    }
});
</script>
</body>
</html>