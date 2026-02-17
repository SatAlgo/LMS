<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

// Admin Access Control
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header("Location: ../index.php");
    exit();
}

// Add this near the top
$set_res = mysqli_query($connection, "SELECT config_value FROM settings WHERE config_key = 'fine_rate'");
$fine_rate = mysqli_fetch_assoc($set_res)['config_value'] ?? 2;

// Handle Delete Request
if (isset($_GET['delete'])) {
    // 🔒 Security: Block Staff from triggering deletion via URL
    if ($_SESSION['role'] !== 'admin') {
        header("Location: manage-users.php?msg=denied");
        exit();
    }

    $id = mysqli_real_escape_string($connection, $_GET['delete']);

    // 1. Fetch the student's PRN before we do anything
    $user_res = mysqli_query($connection, "SELECT prn FROM users WHERE id = '$id'");
    $user_data = mysqli_fetch_assoc($user_res);

    if ($user_data) {
        $prn = $user_data['prn'];

        // 2. Check for ACTIVE issues (Status = 1 means not returned)
        $check_active = mysqli_query($connection, "SELECT id FROM issued_books WHERE prn = '$prn' AND status = 1 LIMIT 1");

        if (mysqli_num_rows($check_active) > 0) {
            // STOP: Cannot delete because they still have a book in their hand
            header("Location: manage-users.php?msg=has_books");
        } else {
            // GO: No active books. Clear history and delete user.
            
            // Start a transaction to ensure both happen or neither happens
            mysqli_begin_transaction($connection);
            try {
                // A. Delete all past transaction history for this PRN
                mysqli_query($connection, "DELETE FROM issued_books WHERE prn = '$prn'");
                
                // B. Delete the student account
                mysqli_query($connection, "DELETE FROM users WHERE id = '$id'");

                mysqli_commit($connection);
                header("Location: manage-users.php?msg=deleted");
            } catch (mysqli_sql_exception $e) {
                mysqli_rollback($connection);
                header("Location: manage-users.php?msg=error");
            }
        }
    }
    exit();
}
?>

<style>
    /* Unified Fixed Layout */
    html, body { height: 100vh; overflow: hidden; background-color: #f4f7f6; }
    .main-wrapper { display: flex; height: 100vh; width: 100vw; }
    .content-area { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; }
    .sticky-header { background: white; border-bottom: 2px solid #eee; padding: 15px 30px; margin-left: 230px; flex-shrink: 0; }
    .main-content { padding: 0 25px 100px 25px; /* ❗ remove TOP padding only */ flex-grow: 1; overflow-y: auto; } /* Scroll only the table area */
    .user-card { border-radius: 10px; border: none; transition: 0.3s; }
    #usersTable thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f8f9fa;
    }
    .table thead th { border-top: none; background: #f8f9fa; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; }
    .avatar-circle { width: 35px; height: 35px; background: #e9ecef; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #6c757d; font-weight: bold; }
    /* Styling for restricted buttons */
    .disabled-action {
        cursor: not-allowed !important;
        opacity: 0.6;
        pointer-events: auto !important; /* Ensures the 'not-allowed' cursor shows */
    }
    .actions-cell {
        width: 160px;
        min-width: 160px;
        vertical-align: middle !important;
    }

    /* Container to keep buttons in a single horizontal line */
    .action-btn-group {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 5px; /* Space between buttons */
    }
</style>

<div class="main-wrapper">
    <?php include "../sidebar.php"; ?>

    <div class="content-area">
        <div class="sticky-header d-flex justify-content-between align-items-center">
            <h3 class="m-0 font-weight-bold"><i class="fas fa-user-graduate text-primary mr-2"></i> Registered Students</h3>
            <!-- <div class="d-flex">
                <input type="text" id="userSearch" class="form-control mr-2" placeholder="Search PRN or Name..." style="width: 250px;">
                <a href="add-user.php" class="btn btn-primary"><i class="fas fa-plus-circle mr-2"></i> Register Student</a>
            </div> -->

            <div class="d-flex">
                <select id="userFilter" class="form-control mr-2" style="width: 170px; font-weight: 500;">
                    <option value="all">All Students</option>
                    <option value="has_fine">⚠️ With Fine</option>
                    <option value="has_books">📚 Books Issued</option>
                </select>
                <input type="text" id="userSearch" class="form-control mr-2" placeholder="Search PRN or Name..." style="width: 250px;">
                <a href="add-user.php" class="btn btn-primary text-nowrap">
                    <i class="fas fa-plus-circle mr-1"></i> Register Student
                </a>
            </div>
        </div>

        <div class="main-content">
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
                    <i class="fas fa-check-circle mr-2"></i> Student account removed successfully.
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>

            <?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'denied'): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm">
                    <i class="fas fa-user-shield mr-2"></i> <strong>Access Denied:</strong> Only Administrators are authorized to Register, Edit, or Delete students.
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>

            <?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'has_books'): ?>
                <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <strong>Action Denied:</strong> This student currently has books issued. Please return the books before deleting the account.
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>

            <?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'fk_error'): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm">
                    <i class="fas fa-times-circle mr-2"></i> <strong>Database Constraint:</strong> Cannot delete student because they have a previous borrowing history. Consider deactivating them instead.
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            

            <div class="card user-card shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0" id="usersTable">
                        <thead>
                            <tr>
                                <th class="pl-4">Student</th>
                                <th>PRN</th>
                                <th>Branch / Div</th>
                                <th>Contact</th>
                                <th>Books Issued</th>
                                <th>Pending Fine</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch all students
                            $query = "SELECT * FROM users ORDER BY created_at DESC";
                            $result = mysqli_query($connection, $query);

                            while ($row = mysqli_fetch_assoc($result)) {
                                $prn = $row['prn'];
                                // Count active issues for each student
                                $count_res = mysqli_query($connection, "SELECT COUNT(*) as total FROM issued_books WHERE prn = '$prn' AND status = 1");
                                $issued_count = mysqli_fetch_assoc($count_res)['total'];
                                
                                // Get first letter for avatar
                                $initial = strtoupper(substr($row['name'], 0, 1));

                                // Inside the while loop, before the <tr>
                                $total_fine = 0;
                                // Check for books that are issued (status=1) and past their due_date
                                $fine_q = mysqli_query($connection, "SELECT due_date FROM issued_books WHERE prn = '$prn' AND status = 1 AND due_date < CURDATE()");

                                while($f_row = mysqli_fetch_assoc($fine_q)) {
                                    $due = new DateTime($f_row['due_date']);
                                    $today = new DateTime();
                                    $diff = $today->diff($due)->days;
                                    $total_fine += ($diff * $fine_rate);
                                }
                            ?>

                               <tr class="student-row" 
                                    data-has-fine="<?php echo ($total_fine > 0) ? 'yes' : 'no'; ?>" 
                                    data-has-books="<?php echo ($issued_count > 0) ? 'yes' : 'no'; ?>">
                                    <td class="pl-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle mr-3"><?php echo $initial; ?></div>
                                            <div>
                                                <div class="font-weight-bold"><?php echo htmlspecialchars($row['name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle"><code><?php echo $prn; ?></code></td>
                                    <td class="align-middle">
                                        <?php echo $row['branch']; ?><br>
                                        <span class="badge badge-light border">Div: <?php echo $row['division']; ?></span>
                                    </td>
                                    <td class="align-middle">
                                        <small><i class="fas fa-phone mr-1"></i> <?php echo $row['mobile']; ?></small>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge badge-pill <?php echo ($issued_count > 0) ? 'badge-primary' : 'badge-light border'; ?>">
                                            <?php echo $issued_count; ?> Books
                                        </span>
                                    </td>
                                    <td class="align-middle">
                                        <?php if ($total_fine > 0): ?>
                                            <span class="badge badge-danger shadow-sm px-2 py-1">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> ₹<?php echo $total_fine; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">No Dues</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle actions-cell">
                                        <div class="action-btn-group">
                                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                                <a href="edit-user.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info" title="Edit Profile">
                                                    <i class="fas fa-user-edit"></i>
                                                </a>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary disabled-action" title="Admin Only" disabled>
                                                    <i class="fas fa-user-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <a href="view-user-details.php?prn=<?php echo $row['prn']; ?>" class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                                <a href="manage-users.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" 
                                                onclick="return confirm('Deleting a student will also remove their borrowing history. Proceed?')" title="Delete Student">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary disabled-action" title="Admin Only" disabled>
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                            <tr id="noUsersRow" style="display:none;">
                                <td colspan="6" class="text-center py-4 text-muted font-weight-bold">
                                    No students found matching your criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>

<script>
$(document).ready(function() {
    function applyFilters() {
        let searchValue = $("#userSearch").val().toLowerCase().trim();
        let filterValue = $("#userFilter").val(); // Get value from dropdown
        let visibleCount = 0;

        $("#usersTable tbody tr.student-row").each(function() {
            let row = $(this);
            let textMatch = row.text().toLowerCase().indexOf(searchValue) > -1;
            let statusMatch = true;

            // Check Dropdown Filter
            if (filterValue === "has_fine") {
                statusMatch = (row.attr('data-has-fine') === 'yes');
            } else if (filterValue === "has_books") {
                statusMatch = (row.attr('data-has-books') === 'yes');
            }

            // Show row only if BOTH match
            if (textMatch && statusMatch) {
                row.show();
                visibleCount++;
            } else {
                row.hide();
            }
        });

        // Toggle "No users found" message
        if (visibleCount === 0) { $("#noUsersRow").show(); } 
        else { $("#noUsersRow").hide(); }
    }

    // Trigger filter when typing OR when changing dropdown
    $("#userSearch").on("keyup", applyFilters);
    $("#userFilter").on("change", applyFilters);
});
</script>
