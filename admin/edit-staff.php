<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage-staff.php");
    exit();
}

$id = mysqli_real_escape_string($connection, $_GET['id']);
$staff = mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM staff_accounts WHERE id = '$id'"));

if (!$staff) {
    header("Location: manage-staff.php");
    exit();
}

$success_msg = "";
$error_msg = "";

if (isset($_POST['update_staff'])) {
    $name = mysqli_real_escape_string($connection, trim($_POST['name']));
    $email = mysqli_real_escape_string($connection, trim(strtolower($_POST['email'])));
    $phone = mysqli_real_escape_string($connection, trim($_POST['phone']));
    $role = mysqli_real_escape_string($connection, $_POST['role']);

    // Check Global Uniqueness
    $check_user = mysqli_query($connection, "SELECT id FROM users WHERE email = '$email' LIMIT 1");
    $check_staff = mysqli_query($connection, "SELECT id FROM staff_accounts WHERE email = '$email' AND id != '$id' LIMIT 1");

    if (mysqli_num_rows($check_user) > 0) {
        $error_msg = "This email is already registered to a Student.";
    } elseif (mysqli_num_rows($check_staff) > 0) {
        $error_msg = "This email is already assigned to another Staff member.";
    } else {
        if (mysqli_query($connection, "UPDATE staff_accounts SET name='$name', email='$email', phone='$phone', role='$role' WHERE id='$id'")) {
            $success_msg = "Profile updated successfully!";
            $staff['name'] = $name; $staff['email'] = $email; $staff['phone'] = $phone; $staff['role'] = $role;
        }
    }
}
?>

<style>
    /* 1. Global Reset to prevent body scroll */
    html, body { 
        height: 100%; 
        margin: 0; 
        padding: 0; 
        overflow: hidden; /* DANGEROUS: disables all scroll */
        background-color: #f4f7f6;
    }

    /* 2. Layout Wrapper */
    .main-wrapper { 
        display: flex; 
        height: 100vh; 
        width: 100vw; 
    }

    /* 3. Content Area - Vertical Column */
    .content-area { 
        flex-grow: 1; 
        display: flex; 
        flex-direction: column; 
        height: 100vh;
        overflow: hidden; /* Keeps header fixed */
    }

    /* 4. Sticky Header - Locked to top */
    .sticky-header { 
        background: white; 
        border-bottom: 2px solid #eee; 
        padding: 20px 40px; 
        margin-left: 240px; /* Width of sidebar */
        flex-shrink: 0; 
        z-index: 1000;
    }

    /* 5. Main Content - The ONLY scrollable part if content overflows */
    .main-content { 
        margin-left: 240px; 
        padding: 40px; 
        flex-grow: 1; 
        overflow-y: auto; 
        display: flex; 
        flex-direction: column; 
        align-items: center;
    }

    .card-edit { 
        border: none; 
        border-radius: 15px; 
        width: 100%; 
        max-width: 700px; 
        background: white; 
        margin-bottom: 20px;
    }
</style>

<div class="main-wrapper">
    <?php include "../sidebar.php"; ?>

    <div class="content-area">
        <div class="sticky-header d-flex justify-content-between align-items-center">
            <h3 class="m-0 font-weight-bold text-dark">
                <i class="fas fa-user-shield text-primary mr-2"></i> Edit Staff Member
            </h3>
            <a href="manage-staff.php" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fas fa-arrow-left mr-2"></i> Back
            </a>
        </div>

        <div class="main-content">
            <?php if ($error_msg): ?>
                <div class="alert alert-danger shadow-sm mb-4 w-100" style="max-width: 700px;">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <?php if ($success_msg): ?>
                <div class="alert alert-success shadow-sm mb-4 w-100" style="max-width: 700px;">
                    <i class="fas fa-check-circle mr-2"></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>

            <div class="card card-edit shadow-sm">
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="form-group mb-4">
                            <label class="font-weight-bold small text-uppercase text-muted">Full Name</label>
                            <input type="text" name="name" class="form-control form-control-lg bg-light border-0" value="<?php echo htmlspecialchars($staff['name']); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group mb-4">
                                <label class="font-weight-bold small text-uppercase text-muted">Official Email</label>
                                <input type="email" name="email" class="form-control form-control-lg bg-light border-0" value="<?php echo htmlspecialchars($staff['email']); ?>" required>
                            </div>
                            <div class="col-md-6 form-group mb-4">
                                <label class="font-weight-bold small text-uppercase text-muted">Phone Number</label>
                                <input type="text" name="phone" class="form-control form-control-lg bg-light border-0" value="<?php echo htmlspecialchars($staff['phone']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="font-weight-bold small text-uppercase text-muted">Access Role</label>
                            <select name="role" class="form-control form-control-lg bg-light border-0">
                                <option value="staff" <?php echo ($staff['role'] == 'staff') ? 'selected' : ''; ?>>Staff (Librarian)</option>
                                <option value="admin" <?php echo ($staff['role'] == 'admin') ? 'selected' : ''; ?>>Admin (Full Access)</option>
                            </select>
                        </div>

                        <hr class="my-4">
                        
                        <div class="d-flex align-items-center">
                            <button type="submit" name="update_staff" class="btn btn-primary px-5 py-2 font-weight-bold shadow-sm mr-3">
                                SAVE CHANGES
                            </button>
                            <span class="text-muted small"><i class="fas fa-info-circle mr-1"></i> Changes take effect immediately.</span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>