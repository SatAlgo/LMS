<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

// STRICT GATEKEEPER: Only Admin can manage other staff
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$message = "";

// 1. Handle Deletion
if (isset($_GET['delete'])) {
    $id = mysqli_real_escape_string($connection, $_GET['delete']);
    // Prevent admin from deleting themselves
    if ($id == $_SESSION['id']) {
        $message = "<div class='alert alert-danger'>You cannot delete your own account.</div>";
    } else {
        mysqli_query($connection, "DELETE FROM staff_accounts WHERE id = '$id'");
        header("Location: manage-staff.php?msg=deleted");
        exit();
    }
}
?>

<style>
    html, body { height: 100vh; overflow: hidden; background-color: #f4f7f6; }
    .main-wrapper { display: flex; height: 100vh; width: 100vw; }
    .content-area { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; margin-left: 10px; }
    .sticky-header { background: white; border-bottom: 2px solid #eee; padding: 20px 30px; margin-left: 230px; flex-shrink: 0; }
    .main-content { padding: 30px; flex-grow: 1; overflow-y: auto; }
    .role-badge-admin { background: #e3f2fd; color: #0d47a1; border: 1px solid #bbdefb; }
    .role-badge-staff { background: #f1f8e9; color: #33691e; border: 1px solid #dcedc8; }
</style>

<div class="main-wrapper">
    <?php include "../sidebar.php"; ?>

    <div class="content-area">
        <div class="sticky-header d-flex justify-content-between align-items-center">
            <h3 class="m-0 font-weight-bold text-dark"><i class="fas fa-user-shield text-secondary mr-2"></i> Staff Management</h3>
            <a href="add-staff.php" class="btn btn-primary font-weight-bold"><i class="fas fa-plus-circle mr-2"></i> Add New Staff</a>
        </div>

        <div class="main-content">
            <?php 
                if(isset($_GET['msg']) && $_GET['msg'] == 'deleted') echo "<div class='alert alert-success'>Account removed successfully.</div>";
                echo $message; 
            ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="pl-4">Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = mysqli_query($connection, "SELECT * FROM staff_accounts ORDER BY role ASC");
                            while($row = mysqli_fetch_assoc($res)):
                                $badge = ($row['role'] === 'admin') ? 'role-badge-admin' : 'role-badge-staff';
                            ?>
                            <tr>
                                <td class="pl-4 py-3 font-weight-bold"><?php echo $row['name']; ?></td>
                                <td><?php echo $row['email']; ?></td>
                                <td>
                                    <span class="badge px-3 py-2 <?php echo $badge; ?>">
                                        <?php echo strtoupper($row['role']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="edit-staff.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info mr-1">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="manage-staff.php?delete=<?php echo $row['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Remove access for this user?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>