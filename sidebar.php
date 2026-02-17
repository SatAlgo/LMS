<?php 
$current_page = basename($_SERVER['PHP_SELF']); 
?>

<style>
    .sidebar { 
        width: 240px; 
        background: #141D49; 
        position: fixed; 
        height: 100vh; 
        z-index: 1050; 
        display: flex;
        flex-direction: column;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    }

    .nav-link {
        color: #d1d4d7 !important;
        padding: 8px 20px;
        margin: 2px 15px;
        border-radius: 8px;
        transition: 0.2s all;
        display: flex;
        align-items: center;
        font-size: 0.95rem;
    }

    .nav-link i { width: 28px; font-size: 1.1rem; }

    .nav-link:hover {
        background: rgba(255, 255, 255, 0.05);
        color: #ffffff !important;
    }

    .active-link {
        background: #007bff !important; 
        color: #ffffff !important; 
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
    }

    .nav-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: #6c757d;
        margin: 20px 0 8px 25px;
        font-weight: 700;
    }

    /* Quick Action Badge color */
    .badge-loan { background: #ffc107; color: #141D49; font-size: 10px; margin-left: auto; }
</style>

<div class="sidebar">
    <div class="pt-1 text-center">
        <!-- <h5 class="text-white font-weight-bold mb-0">MIT Library</h5>
        <p class="small text-muted mb-0" style="font-size: 10px;">v1.0.5 Stable</p> -->
    </div>

    <nav class="nav flex-column mt-2">
        <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active-link' : ''; ?>" 
           href="<?php echo BASE_URL . ($_SESSION['role'] == 'admin' ? 'admin/dashboard.php' : 'staff/dashboard.php'); ?>">
            <i class="fas fa-desktop"></i> Dashboard
        </a>

        <div class="nav-label">Circulation</div>
        
        <a class="nav-link <?php echo ($current_page == 'issue-book.php') ? 'active-link' : ''; ?>" 
           href="<?php echo BASE_URL; ?>admin/issue-book.php">
            <i class="fas fa-plus-circle text-success"></i> Issue Book
        </a>

        <a class="nav-link <?php echo ($current_page == 'return-book.php') ? 'active-link' : ''; ?>" 
           href="<?php echo BASE_URL; ?>admin/return-book.php">
            <i class="fas fa-undo text-warning"></i> Return Book
        </a>

        <a class="nav-link <?php echo ($current_page == 'view-issued-books.php') ? 'active-link' : ''; ?>" 
           href="<?php echo BASE_URL; ?>admin/view-issued-books.php">
            <i class="fas fa-hand-holding"></i> Active Loans
        </a>

        <div class="nav-label">Management</div>

        <a class="nav-link <?php echo ($current_page == 'manage-books.php') ? 'active-link' : ''; ?>" 
           href="<?php echo BASE_URL; ?>admin/manage-books.php">
            <i class="fas fa-book"></i> Catalog
        </a>

        <a class="nav-link <?php echo ($current_page == 'manage-users.php') ? 'active-link' : ''; ?>" 
           href="<?php echo BASE_URL; ?>admin/manage-users.php">
            <i class="fas fa-user-graduate"></i> Students
        </a>

        <?php if($_SESSION['role'] === 'admin'): ?>
            <div class="nav-label">Administration</div>
            
            <a class="nav-link <?php echo ($current_page == 'fine-stats.php') ? 'active-link' : ''; ?>" 
            href="<?php echo BASE_URL; ?>admin/fine-stats.php">
                <i class="fas fa-chart-pie"></i> Reports
            </a>

            <a class="nav-link <?php echo ($current_page == 'spend-analysis.php') ? 'active-link' : ''; ?>" 
            href="<?php echo BASE_URL; ?>admin/spend-analysis.php">
                <i class="fas fa-file-invoice-dollar"></i> Asset Analysis
            </a>

            <a class="nav-link <?php echo ($current_page == 'manage-staff.php' || $current_page == 'add-staff.php') ? 'active-link' : ''; ?>" 
            href="<?php echo BASE_URL; ?>admin/manage-staff.php">
                <i class="fas fa-shield-alt"></i> Staff
            </a>
            
            <a class="nav-link <?php echo ($current_page == 'settings.php') ? 'active-link' : ''; ?>" 
            href="<?php echo BASE_URL; ?>admin/settings.php">
                <i class="fas fa-sliders-h"></i> Settings
            </a>
        <?php endif; ?>
    </nav>

    <div class="mt-auto mb-4 px-3">
        <hr class="bg-secondary opacity-25">
        <a class="nav-link text-danger" href="<?php echo BASE_URL; ?>logout.php">
            <i class="fas fa-power-off"></i> Logout
        </a>
    </div>
</div>