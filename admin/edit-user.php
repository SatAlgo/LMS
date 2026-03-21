<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

// Admin Access Control
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') { header("Location: manage-users.php?msg=denied"); exit(); }

// 1. Fetch Existing Data
$id = mysqli_real_escape_string($connection, $_GET['id']);
$query = "SELECT * FROM users WHERE id = '$id'";
$res = mysqli_query($connection, $query);
$user = mysqli_fetch_assoc($res);

if (!$user) {
    header("Location: manage-users.php");
    exit();
}

// 2. Handle Update Logic
$success_msg = "";
$error_msg = "";

if (isset($_POST['update_user'])) {

    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $mobile = mysqli_real_escape_string($connection, $_POST['mobile']);
    $branch = mysqli_real_escape_string($connection, $_POST['branch']);
    $division = mysqli_real_escape_string($connection, $_POST['division']);

    // 🔒 DUPLICATE EMAIL CHECK (excluding current user)
    $check_email = mysqli_query(
        $connection,
        "SELECT id FROM users WHERE email = '$email' AND id != '$id' LIMIT 1"
    );

    if (mysqli_num_rows($check_email) > 0) {

        // ❌ Email already used by another student
        $error_msg = "This email address is already assigned to another student.";

    } else {

        // ✅ Safe to update
        $update_query = "
            UPDATE users 
            SET 
                name = '$name',
                email = '$email',
                mobile = '$mobile',
                branch = '$branch',
                division = '$division'
            WHERE id = '$id'
        ";

        if (mysqli_query($connection, $update_query)) {
            $success_msg = "Student profile updated successfully!";

            // Refresh form data
            $user['name'] = $name;
            $user['email'] = $email;
            $user['mobile'] = $mobile;
            $user['branch'] = $branch;
            $user['division'] = $division;
        }
    }
}

?>

<style>
    /* Fixed Layout: Consistent with Dashboard */
    html, body { height: 100vh; overflow: hidden; background-color: #f4f7f6; }
    .main-wrapper { display: flex; height: 100vh; width: 100vw; }
    .sidebar { width: 240px; background: #343a40; flex-shrink: 0; z-index: 1050; position: fixed; height: 100vh; }
    .content-area { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; margin-left: 10px; }
    .sticky-header { background: white; border-bottom: 2px solid #eee; padding: 25px 30px; margin-left: 230px; flex-shrink: 0; }
    .main-content { padding: 40px; flex-grow: 1; overflow-y: auto; }
    .card-edit { border: none; border-radius: 12px; max-width: 700px; }
</style>

<div class="main-wrapper">
    <?php include "../sidebar.php"; ?>

    <div class="content-area">
        <div class="sticky-header">
            <h3 class="m-0 ml-5 pl-5 font-weight-bold">
                <i class="fas fa-user-edit text-primary mr-2"></i> Edit Student Profile
            </h3>
        </div>

        <div class="main-content">

            <?php if ($error_msg): ?>
                <div class="alert alert-danger shadow-sm mb-4">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <?php if ($success_msg): ?>
                <div class="alert alert-success shadow-sm mb-4"><?php echo $success_msg; ?></div>
            <?php endif; ?>

            <div class="card card-edit shadow-sm ml-5">
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold">PRN (ReadOnly)</label>
                                <input type="text" class="form-control bg-light" value="<?php echo $user['prn']; ?>" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold">Branch</label>
                                <select name="branch" class="form-control">
                                    <?php 
                                    // Fetch branches from settings
                                    $branches = explode(',', mysqli_fetch_assoc(mysqli_query($connection, "SELECT config_value FROM settings WHERE config_key='branches'"))['config_value']);
                                    foreach($branches as $b) {
                                        $selected = ($user['branch'] == $b) ? "selected" : "";
                                        echo "<option value='$b' $selected>$b</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="font-weight-bold">Division</label>
                                <input type="text" name="division" class="form-control" value="<?php echo $user['division']; ?>" maxlength="1">
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="font-weight-bold">Mobile</label>
                                <input type="text" name="mobile" class="form-control" value="<?php echo $user['mobile']; ?>" required>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex">
                            <button type="submit" name="update_user" class="btn btn-primary px-5 font-weight-bold mr-2">
                                SAVE CHANGES
                            </button>
                            <a href="manage-users.php" class="btn btn-outline-secondary px-4">CANCEL</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>