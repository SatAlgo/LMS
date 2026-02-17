<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

$mail_supported = file_exists("../includes/mail-helper.php");
if ($mail_supported) {
    require_once "../includes/mail-helper.php";
}

if ($_SESSION['role'] !== 'admin') { header("Location: manage-users.php?msg=denied"); exit(); }

// Admin Access Control
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header("Location: ../index.php");
    exit();
}

// 1. DYNAMIC PASSWORD: Fetch the default password from settings FIRST
// This ensures $raw_password is available even before the form is submitted
$pass_res = mysqli_query($connection, "SELECT config_value FROM settings WHERE config_key = 'default_password' LIMIT 1");
$pass_row = mysqli_fetch_assoc($pass_res);
$raw_password = $pass_row['config_value'] ?? '12345'; 

$success_msg = "";
$error_msg = "";

if (isset($_POST['add_user'])) {
    $prn = mysqli_real_escape_string($connection, $_POST['prn']);
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $mobile = mysqli_real_escape_string($connection, $_POST['mobile']);
    $branch = mysqli_real_escape_string($connection, $_POST['branch']);
    $division = mysqli_real_escape_string($connection, $_POST['division']);
    $lib_card = "MIT" . $prn; 
    
    // Hash the dynamic password fetched above
    $password = password_hash($raw_password, PASSWORD_DEFAULT);

    // Check if PRN or Email already exists
    $check = mysqli_query($connection, "SELECT id FROM users WHERE prn='$prn' OR email='$email'");
    
    if (mysqli_num_rows($check) > 0) {
        $error_msg = "Error: Student with this PRN or Email already exists.";
    } else {
        $query = "INSERT INTO users (prn, name, email, password, mobile, branch, division, library_card_no) 
                  VALUES ('$prn', '$name', '$email', '$password', '$mobile', '$branch', '$division', '$lib_card')";
        
        if (mysqli_query($connection, $query)) {

            // --- TOGGLE FEATURE CHECK ---
            if ($mail_supported && function_exists('isEmailEnabled') && isEmailEnabled($connection, 'mail_account_creation')) {

                $subject = "Library Account Created | MIT AOE";
                $body = "Hello $name,\n\n" .
                        "Your Library Management System account has been created successfully.\n\n" .
                        "Login Credentials:\n" .
                        "PRN / Email : $prn\n" .
                        "Default Password : $raw_password\n\n" .
                        "Library Card No: $lib_card\n\n" .
                        "Please login and change your password immediately.\n\n" .
                        "Regards,\nMIT AOE Library";

                try {
                    sendLMSMail($email, $subject, $body);
                } catch (Exception $e) {
                    // Silently fail mail
                }
            }

            $success_msg = "Student registered successfully! Password '$raw_password' emailed to student.";
        } else {
            $error_msg = "Database Error: " . mysqli_error($connection);
        }
    }
}
?>

<style>
    /* Fixed Layout - No Page Scrolling */
    html, body { height: 100vh; overflow: hidden; background-color: #f4f7f6; margin: 0; padding: 0; }
    .main-wrapper { display: flex; height: 100vh; width: 100vw; overflow: hidden; }
    
    .content-area { 
        flex-grow: 1; 
        display: flex; 
        flex-direction: column; 
        overflow: hidden; 
        margin-left: 10px;
    }

    .sticky-header { 
        background: white; 
        border-bottom: 2px solid #eee; 
        padding: 25px 30px; 
        margin-left: 230px;
        flex-shrink: 0; 
    }

    .main-content { 
        padding: 40px; 
        flex-grow: 1; 
        overflow-y: auto; 
        display: flex; 
        justify-content: center; 
        align-items: flex-start; 
    }

    .card-add { border: none; border-radius: 12px; width: 100%; max-width: 750px; }
</style>

<div class="main-wrapper">
    <?php include "../sidebar.php"; ?>

    <div class="content-area">
        <div class="sticky-header">
            <h3 class="m-0 font-weight-bold">
                <i class="fas fa-user-plus text-primary mr-2"></i> Register New Student
            </h3>
        </div>

        <div class="main-content">
            <div class="card card-add shadow-sm">
                <div class="card-body p-4">
                    <?php if ($success_msg): ?>
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
                            <i class="fas fa-check-circle mr-2"></i> <?php echo $success_msg; ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_msg): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm">
                            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_msg; ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" autocomplete="off">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold small text-uppercase">Full Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Enter student name" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold small text-uppercase">PRN Number</label>
                                <input type="text" name="prn" class="form-control" placeholder="e.g. 202301040138" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold small text-uppercase">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="student@mitaoe.ac.in" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold small text-uppercase">Branch</label>
                                <select name="branch" class="form-control" required>
                                    <option value="">Select Branch</option>
                                    <?php 
                                    $b_res = mysqli_query($connection, "SELECT config_value FROM settings WHERE config_key='branches'");
                                    $b_data = mysqli_fetch_assoc($b_res);
                                    $branches = explode(',', $b_data['config_value']);
                                    foreach($branches as $b) {
                                        $b = trim($b);
                                        echo "<option value='$b'>$b</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="font-weight-bold small text-uppercase">Division</label>
                                <input type="text" name="division" class="form-control text-center" placeholder="A/B/C" maxlength="1" required>
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="font-weight-bold small text-uppercase">Mobile</label>
                                <input type="text" name="mobile" class="form-control" placeholder="10-digit" maxlength="10" required>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button type="submit" name="add_user" class="btn btn-primary px-5 font-weight-bold shadow-sm py-2">
                                    <i class="fas fa-save mr-2"></i> REGISTER STUDENT
                                </button>
                                <a href="manage-users.php" class="btn btn-light border px-4 ml-2">CANCEL</a>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-info-circle mr-1"></i> Current Default Password: <b><?php echo $raw_password; ?></b>
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>