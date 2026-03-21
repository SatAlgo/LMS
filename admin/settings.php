<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$message = "";

if (isset($_POST['update_settings'])) {
    // 1. Handle regular inputs (Limits & Fines)
    foreach ($_POST['config'] as $key => $value) {
        $key = mysqli_real_escape_string($connection, $key);
        $value = mysqli_real_escape_string($connection, $value);
        mysqli_query($connection, "UPDATE settings SET config_value = '$value' WHERE config_key = '$key'");
    }

    // 2. Handle Individual Email Toggles
    $toggles = ['mail_account_creation', 'mail_book_issuance', 'mail_return_reminders'];
    foreach ($toggles as $t) {
        $val = isset($_POST[$t]) ? '1' : '0';
        mysqli_query($connection, "UPDATE settings SET config_value = '$val' WHERE config_key = '$t'");
    }

    $message = "System configuration updated successfully!";
}

// Fetch current settings
$settings = [];
$res = mysqli_query($connection, "SELECT * FROM settings");
while ($row = mysqli_fetch_assoc($res)) {
    $settings[$row['config_key']] = $row['config_value'];
}
?>

<style>
    html, body { height: 100vh; overflow: hidden; background-color: #f4f7f6; }
    .main-wrapper { display: flex; height: 100vh; width: 100vw; }
    .content-area { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; margin-left: 10px; }
    
    /* Improved Sticky Header to accommodate the button */
    .sticky-header { 
        background: white; 
        border-bottom: 2px solid #eee; 
        padding: 15px 30px; 
        margin-left: 230px; 
        flex-shrink: 0; 
        display: flex; 
        justify-content: space-between; 
        align-items: center;
        z-index: 1000;
    }
    
    .main-content { padding: 30px; flex-grow: 1; overflow-y: auto; }
    .settings-card { background: white; border-radius: 12px; border: none; }
    
    /* Toggle Switch */
    .switch { position: relative; display: inline-block; width: 44px; height: 22px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 22px; }
    .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #28a745; }
    input:checked + .slider:before { transform: translateX(22px); }
</style>

<div class="main-wrapper">
    <?php include "../sidebar.php"; ?>

    <div class="content-area">
        <form action="" method="POST">
            
            <div class="sticky-header">
                <h3 class="m-0 font-weight-bold text-dark">
                    <i class="fa-solid fa-gear text-primary mr-2"></i>Configuration
                </h3>
                <button type="submit" name="update_settings" class="btn btn-primary px-4 font-weight-bold shadow-sm">
                    <i class="fas fa-save mr-2"></i> SAVE ALL SETTINGS
                </button>
            </div>

            <div class="main-content">
                <?php if($message): ?>
                    <div class="alert alert-success shadow-sm border-0">
                        <i class="fas fa-check-circle mr-2"></i> <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="card settings-card shadow-sm p-4">
                    
                    <h5 class="font-weight-bold text-primary mb-3"><i class="fas fa-rupee-sign mr-1"></i>Fine & Issue Rules</h5>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold small text-uppercase text-muted">Standard Fine (₹/day)</label>
                            <input type="number" name="config[fine_rate]" class="form-control" value="<?php echo $settings['fine_rate'] ?? 2; ?>" required>
                            <small class="text-muted">Rate for first 15 days.</small>
                        </div>
                        
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold small text-uppercase text-muted">Late Fine (₹/day)</label>
                            <input type="number" name="config[fine_rate_late]" class="form-control" value="<?php echo $settings['fine_rate_late'] ?? 5; ?>" required>
                            <small class="text-muted">Rate after 15 days.</small>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold small text-uppercase text-muted">Max Books Limit</label>
                            <input type="number" name="config[issue_limit]" class="form-control" value="<?php echo $settings['issue_limit'] ?? 3; ?>" required>
                            <small class="text-muted">Max books per PRN.</small>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Default Loan Period (Days)</label>
                            <input type="number" name="config[default_loan_period]" class="form-control" 
                                value="<?php echo $settings['default_loan_period'] ?? 14; ?>">
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold small text-uppercase text-muted">Default Student Password</label>
                            <input type="text" name="config[default_password]" class="form-control" value="<?php echo $settings['default_password'] ?? '12345'; ?>" required>
                            <small class="text-muted">For new student accounts.</small>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="font-weight-bold text-primary mb-3"><i class="fas fa-bell mr-2"></i> Individual Email Notifications</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center bg-light p-3 rounded">
                                <label class="switch mr-3 mb-0">
                                    <input type="checkbox" name="mail_account_creation" <?php echo ($settings['mail_account_creation'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                                <div>
                                    <p class="mb-0 font-weight-bold small">Account Creation</p>
                                    <small class="text-muted">Welcome & credentials.</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center bg-light p-3 rounded">
                                <label class="switch mr-3 mb-0">
                                    <input type="checkbox" name="mail_book_issuance" <?php echo ($settings['mail_book_issuance'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                                <div>
                                    <p class="mb-0 font-weight-bold small">Book Issuance</p>
                                    <small class="text-muted">Issue confirmation.</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center bg-light p-3 rounded">
                                <label class="switch mr-3 mb-0">
                                    <input type="checkbox" name="mail_return_reminders" <?php echo ($settings['mail_return_reminders'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                                <div>
                                    <p class="mb-0 font-weight-bold small">Return Reminders</p>
                                    <small class="text-muted">Overdue notifications.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="font-weight-bold text-primary mb-3"><i class="fas fa-university mr-2"></i> Academic Branches</h5>
                    <div class="form-group">
                        <textarea name="config[branches]" class="form-control" rows="4" placeholder="Computer, Mechanical, Civil..."><?php echo $settings['branches'] ?? ''; ?></textarea>
                        <small class="text-muted">Enter branch names separated by commas.</small>
                    </div>

                </div>
            </div>
        </form> </div>
</div>

<?php include "../includes/footer.php"; ?>