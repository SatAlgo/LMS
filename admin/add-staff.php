<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

// PHPMailer Headers (Essential for reliable email)
require '../includes/PHPMailer/Exception.php';
require '../includes/PHPMailer/PHPMailer.php';
require '../includes/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$message = "";

// Handle the redirect message to prevent form resubmission
if (isset($_GET['success'])) {
    $message = "<div class='alert alert-success shadow-sm'><i class='fas fa-check-circle mr-2'></i> Staff account created and credentials emailed!</div>";
}

if (isset($_POST['add_staff'])) {
    $name = mysqli_real_escape_string($connection, trim($_POST['name']));
    $email = mysqli_real_escape_string($connection, trim(strtolower($_POST['email'])));
    $phone = mysqli_real_escape_string($connection, trim($_POST['phone']));
    $role = mysqli_real_escape_string($connection, $_POST['role']);
    $password = $_POST['password']; 
    
    // STEP 1: Global Uniqueness Check (Option A)
    if (isEmailGlobalUnique($connection, $email)) {
        $message = "<div class='alert alert-danger shadow-sm'><i class='fas fa-exclamation-circle mr-2'></i> This email is already registered in the system (Staff or Student).</div>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO staff_accounts (name, email, phone, password, role) 
                  VALUES ('$name', '$email', '$phone', '$hashed_password', '$role')";
        
        if (mysqli_query($connection, $query)) {

            // STEP 2: Automated Email System (using PHPMailer)
            if (isEmailEnabled($connection, 'mail_account_creation')) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'algorithmseven@gmail.com'; 
                    $mail->Password = 'rafj opvi ncmb hnru'; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('algorithmseven@gmail.com', 'MIT AOE Library');
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = "Welcome to MIT AOE Library Management System";
                    
                    $display_role = strtoupper($role);
                    $mail->Body = "
                        <div style='font-family: Arial, sans-serif; border: 1px solid #eee; padding: 20px;'>
                            <h2 style='color: #141D49;'>MIT AOE Central Library</h2>
                            <p>Hello <strong>$name</strong>, your staff account has been created successfully.</p>
                            <p><strong>Your Credentials:</strong></p>
                            <ul>
                                <li><strong>Email:</strong> $email</li>
                                <li><strong>Password:</strong> $password</li>
                                <li><strong>Access Level:</strong> $display_role</li>
                            </ul>
                            <p>Please login and change your password for security.</p>
                        </div>";

                    $mail->send();
                } catch (Exception $e) {
                    // Silence error or log it - the account is already created in DB
                }
            }

            header("Location: add-staff.php?success=1");
            exit();
        } else {
            $message = "<div class='alert alert-danger shadow-sm'>Error: " . mysqli_error($connection) . "</div>";
        }
    }
}
?>

<style>
    html, body { height: 100vh; overflow: hidden; background-color: #f4f7f6; }
    .main-wrapper { display: flex; height: 100vh; width: 100vw; }
    .content-area { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; margin-left: 10px; }
    .sticky-header { background: white; border-bottom: 2px solid #eee; padding: 20px 30px; margin-left: 230px; flex-shrink: 0; }
    .main-content { padding: 30px; flex-grow: 1; overflow-y: auto; display: flex; justify-content: center; }
    .form-card { background: white; border-radius: 15px; border: none; width: 100%; max-width: 600px; height: fit-content; }
</style>

<div class="main-wrapper">
    <?php include "../sidebar.php"; ?>

    <div class="content-area">
        <div class="sticky-header d-flex justify-content-between align-items-center">
            <h3 class="m-0 font-weight-bold text-dark"><i class="fas fa-user-plus text-primary mr-2"></i> Register Staff</h3>
            <a href="manage-staff.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-2"></i> Back to List</a>
        </div>

        <div class="main-content">
            <div class="card form-card shadow-sm p-4">
                <?php echo $message; ?>
                
                <form action="" method="POST" autocomplete="off">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small text-uppercase">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter Full Name" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold small text-uppercase">Official Email</label>
                                <input type="email" name="email" class="form-control" placeholder="email@mitaoe.ac.in" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold small text-uppercase">Mobile Number</label>
                                <input type="text" name="phone" class="form-control" placeholder="e.g. 98XXXXXXXX" maxlength="10" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold small text-uppercase">Account Role</label>
                                <select name="role" class="form-control" required>
                                    <option value="staff">Staff (Librarian)</option>
                                    <option value="admin">Admin (Full Access)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold small text-uppercase">Initial Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required style="border-right:none;">
                                    <div class="input-group-append">
                                        <span class="input-group-text" id="togglePassword" style="cursor:pointer; background:white; border-left:none;">
                                            <i class="fas fa-eye text-muted"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    
                    <button type="submit" name="add_staff" class="btn btn-primary btn-block font-weight-bold py-2 shadow-sm">
                        CREATE STAFF ACCOUNT
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>

<script>
$(document).ready(function() {
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
</script>