<?php
session_start();
require_once "includes/config.php";

$message = "";
$message_type = "";

if (isset($_POST['signup'])) {
    $name = mysqli_real_escape_string($connection, trim($_POST['name']));
    $email = mysqli_real_escape_string($connection, trim($_POST['email']));
    $mobile = mysqli_real_escape_string($connection, trim($_POST['mobile']));
    $prn = mysqli_real_escape_string($connection, trim($_POST['prn']));
    $branch = mysqli_real_escape_string($connection, $_POST['branch']);
    $division = mysqli_real_escape_string($connection, $_POST['division']);

    // 1. Extract Year and Generate Library Card Number
    // Example: 202301040137 -> Year 2023, Last digits 0137 -> Card: MIT20230137
    $year = substr($prn, 0, 4); 
    $last_digits = substr($prn, -4);
    $library_card_no = "MIT" . $year . $last_digits;

    // 2. Set Default Password and Hash it
    $default_password = "12345678";
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

    // 3. Check if PRN or Email already exists
    $check_query = "SELECT id FROM users WHERE prn = '$prn' OR email = '$email' LIMIT 1";
    $check_result = mysqli_query($connection, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $message = "Error: PRN or Email already registered.";
        $message_type = "danger";
    } else {
        // 4. Insert User
        $insert_query = "INSERT INTO users (name, email, password, mobile, prn, branch, division, library_card_no) 
                         VALUES ('$name', '$email', '$hashed_password', '$mobile', '$prn', '$branch', '$division', '$library_card_no')";
        
        if (mysqli_query($connection, $insert_query)) {
            $message = "Registration Successful! Your Library Card No is: <strong>$library_card_no</strong>. Default password is: 12345678";
            $message_type = "success";
        } else {
            $message = "Database Error: Could not register user.";
            $message_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LMS | Student Registration</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; padding: 50px 0; }
        .signup-card { max-width: 600px; margin: auto; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn-primary { background-color: #343a40; border: none; }
    </style>
</head>
<body>

<div class="container">
    <div class="card signup-card p-4">
        <h3 class="text-center font-weight-bold mb-4">Student Registration</h3>

        <?php if($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> text-center small"><?php echo $message; ?></div>
        <?php endif; ?>

        <form action="" method="POST" autocomplete="off">
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="small font-weight-bold">Full Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="John Doe">
                </div>
                <div class="col-md-6 form-group">
                    <label class="small font-weight-bold">Email ID</label>
                    <input type="email" name="email" class="form-control" required placeholder="john@example.com">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="small font-weight-bold">Mobile Number</label>
                    <input type="text" name="mobile" class="form-control" required placeholder="98XXXXXXXX">
                </div>
                <div class="col-md-6 form-group">
                    <label class="small font-weight-bold">PRN Number</label>
                    <input type="text" name="prn" class="form-control" required placeholder="20230104XXXX">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="small font-weight-bold">Branch</label>
                    <select name="branch" class="form-control">
                        <option value="Computer Engineering">Computer Engineering</option>
                        <option value="IT">IT</option>
                        <option value="Mechanical">Mechanical</option>
                        <option value="Civil">Civil</option>
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label class="small font-weight-bold">Division</label>
                    <select name="division" class="form-control">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                    </select>
                </div>
            </div>

            <div class="alert alert-info small mt-2">
                <strong>Note:</strong> Your default password will be <strong>12345678</strong>. Please change it after your first login.
            </div>

            <button type="submit" name="signup" class="btn btn-primary btn-block font-weight-bold mt-4">Register Now</button>
        </form>

        <div class="text-center mt-3">
            <span class="small text-muted">Already have an account? <a href="index.php">Login Here</a></span>
        </div>
    </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>