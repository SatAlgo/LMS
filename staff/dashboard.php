<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

// Access Control: Strict check for Staff only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}

// 1. Fetch Real-time Stats for Staff
$total_books = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM books"))['count'];
$issued_books = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM books WHERE status = 0"))['count'];
$available_books = $total_books - $issued_books;

// 2. Fetch Overdue Count
$today = date('Y-m-d');
$overdue_res = mysqli_query($connection, "SELECT COUNT(*) as count FROM issued_books WHERE status = 1 AND due_date < '$today'");
$overdue_count = mysqli_fetch_assoc($overdue_res)['count'];
?>

<style>
    /* 1. Lock the entire viewport */
    html, body { 
        height: 100vh; 
        width: 100vw;
        overflow: hidden !important; 
        background-color: #f4f7f6; 
        margin: 0; 
    }

    /* 2. Create the flex container for sidebar + content */
    .main-wrapper { 
        display: flex; 
        height: 100vh; 
        width: 100vw; 
        overflow: hidden;
    }
    
    /* 3. The area to the right of the sidebar */
    .content-area { 
        flex-grow: 1; 
        display: flex; 
        flex-direction: column; 
        height: 100vh;
        overflow: hidden; 
    }

    /* 4. The Top Header (Pinned) */
    .sticky-header { 
        background: white; 
        border-bottom: 2px solid #eee; 
        padding: 15px 30px; 
        margin-left: 230px; /* Sidebar width offset */
        flex-shrink: 0; 
    }

    /* 5. The Content Body (Static/No Scroll) */
    .main-content { 
        padding: 20px 30px; 
        margin-left: 230px; /* Sidebar width offset */
        flex-grow: 1; 
        overflow: hidden; /* This removes the scrollbar */
        display: flex;
        flex-direction: column;
    }

    /* Prevent Header Wrapping for ACC NO. */
    .table thead th { 
        white-space: nowrap; 
        border-top: none; 
        background: #f8f9fa; 
        font-size: 0.85rem; 
        text-transform: uppercase; 
    }

    /* Force single-line rows and handle long titles */
    .table td { 
        white-space: nowrap; 
        vertical-align: middle; 
    }

    /* Specific constraint for Book Title column */
    .book-title-cell {
        max-width: 250px; /* Adjust based on your screen size */
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* 6. Dashboard specific elements */
    .card-stats { border-left: 4px solid #007bff; border-radius: 8px; }
    .table-responsive { overflow: hidden; } /* Ensure table doesn't force a scroll */
</style>

<div class="main-wrapper">
    <?php include "../sidebar.php"; ?>

    <div class="content-area">
        <div class="sticky-header d-flex justify-content-between align-items-center">
            <h3 class="m-0 font-weight-bold text-dark"><i class="fas fa-user-tie text-primary mr-2"></i> Staff Terminal</h3>
            <div class="text-right">
                <span class="badge badge-light border px-3 py-2">
                    <i class="fas fa-calendar-alt mr-2 text-primary"></i><?php echo date('d M, Y'); ?>
                </span>
            </div>
        </div>

        <div class="main-content">
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card card-stats shadow-sm p-3 bg-white">
                        <small class="text-muted font-weight-bold">INVENTORY</small>
                        <h3 class="m-0 font-weight-bold"><?php echo $total_books; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stats shadow-sm p-3 bg-white border-success">
                        <small class="text-muted font-weight-bold">AVAILABLE</small>
                        <h3 class="m-0 font-weight-bold text-success"><?php echo $available_books; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stats shadow-sm p-3 bg-white border-warning">
                        <small class="text-muted font-weight-bold">ON LOAN</small>
                        <h3 class="m-0 font-weight-bold text-warning"><?php echo $issued_books; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stats shadow-sm p-3 bg-white border-danger">
                        <small class="text-muted font-weight-bold">OVERDUE</small>
                        <h3 class="m-0 font-weight-bold text-danger"><?php echo $overdue_count; ?></h3>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0" style="border-radius: 12px;">
                        <div class="card-header bg-white py-3 border-0">
                            <h6 class="m-0 font-weight-bold"><i class="fas fa-history mr-2 text-primary"></i> Live Circulation Queue</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th class="pl-2">ACC NO.</th>
                                            <th>Book Title</th>
                                            <th>Student PRN</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $q = "SELECT ib.*, b.title FROM issued_books ib 
                                              JOIN books b ON ib.accession_number = b.accession_number 
                                              WHERE ib.status = 1 ORDER BY ib.issue_date DESC LIMIT 6";
                                        $res = mysqli_query($connection, $q);
                                        while($row = mysqli_fetch_assoc($res)):
                                        ?>
                                        <tr>
                                            <td class="pl-4 font-weight-bold"><code><?php echo $row['accession_number']; ?></code></td>
                                            <td><?php echo htmlspecialchars(substr($row['title'], 0, 40)); ?>...</td>
                                            <td class="font-weight-bold text-dark"><?php echo $row['prn']; ?></td>
                                            <td><span class="badge badge-warning">Issued</span></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 font-weight-bold">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <a href="../admin/issue-book.php" class="btn btn-primary btn-block py-2 mb-3 shadow-sm">
                                <i class="fas fa-plus-circle mr-2"></i> Issue Book
                            </a>
                            <a href="../admin/return-book.php" class="btn btn-warning btn-block py-2 mb-3 text-white font-weight-bold shadow-sm">
                                <i class="fas fa-undo mr-2"></i> Return Book
                            </a>
                            <a href="../admin/manage-users.php" class="btn btn-info btn-block py-2 shadow-sm">
                                <i class="fas fa-users mr-2"></i> View Students
                            </a>
                        </div>
                    </div>
                </div>
            </div> </div> </div> </div>

<?php include "../includes/footer.php"; ?>