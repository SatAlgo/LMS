<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

// Admin Access Control
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Fetch Key Metrics
$total_users = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM users"))['count'];
$total_books = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM books"))['count'];
$issued_now = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM issued_books WHERE status = 1"))['count'];
$total_fine = mysqli_fetch_assoc(mysqli_query($connection, "SELECT SUM(fine) as total FROM issued_books WHERE status = 0"))['total'] ?? 0;
?>

<style>
    /* Prevent page scroll and set unified layout */
    html, body { height: 100vh; overflow: hidden; background-color: #f4f7f6; }
    .main-wrapper { display: flex; height: 100vh; width: 100vw; }
    
    /* Content Area handles sidebar offset */
    .content-area { 
        flex-grow: 1; 
        display: flex; 
        flex-direction: column; 
        overflow: hidden; 
        margin-left: 0; /* Fixed sidebar width offset */
    }

    .sticky-header { 
        background: white; 
        border-bottom: 2px solid #eee; 
        padding: 15px 30px; 
        margin-left: 230px;
        flex-shrink: 0; 
    }

    .main-content { 
        padding: 20px 30px; 
        flex-grow: 1; 
        overflow-y: auto; /* Internal scrolling only */
    }

    .card-stats { border-left: 4px solid #007bff; border-radius: 8px; }
    .table thead th { border-top: none; background: #f8f9fa; font-size: 0.85rem; text-transform: uppercase; }
</style>

<div class="main-wrapper">
    <?php include "../sidebar.php"; ?>

    <div class="content-area">
        <div class="sticky-header d-flex justify-content-between align-items-center">
            <h3 class="m-0 font-weight-bold text-dark">Admin Dashboard</h3>
            <div class="text-right">
                <span class="badge badge-light border px-3 py-2">Welcome, <?php echo $_SESSION['username'] ?? 'Admin'; ?></span>
            </div>
        </div>

        <div class="main-content">
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card card-stats shadow-sm p-3 bg-white">
                        <small class="text-muted font-weight-bold">STUDENTS</small>
                        <h3 class="m-0 font-weight-bold"><?php echo $total_users; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stats shadow-sm p-3 bg-white border-success">
                        <small class="text-muted font-weight-bold">TOTAL COPIES</small>
                        <h3 class="m-0 font-weight-bold text-success"><?php echo $total_books; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stats shadow-sm p-3 bg-white border-warning">
                        <small class="text-muted font-weight-bold">CURRENTLY ISSUED</small>
                        <h3 class="m-0 font-weight-bold text-warning"><?php echo $issued_now; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stats shadow-sm p-3 bg-white border-danger">
                        <small class="text-muted font-weight-bold">TOTAL FINE (₹)</small>
                        <h3 class="m-0 font-weight-bold text-danger"><?php echo number_format($total_fine, 2); ?></h3>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 font-weight-bold"><i class="fas fa-history mr-2 text-primary"></i> Recent Transactions</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>PRN</th>
                                            <th>Acc No.</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Fetches the 6 most recent transactions including exact time
                                        $recent = mysqli_query($connection, "SELECT * FROM issued_books ORDER BY issue_date DESC LIMIT 6");

                                        while($row = mysqli_fetch_assoc($recent)) {
                                            $status = $row['status'] == 1 
                                                ? '<span class="badge badge-warning">Issued</span>' 
                                                : '<span class="badge badge-success">Returned</span>';
                                                
                                            // Changed date format to include Time (H:i)
                                            $display_date = date('d M, g:i A', strtotime($row['issue_date']));

                                            echo "<tr>
                                                    <td class='font-weight-bold'>{$row['prn']}</td>
                                                    <td><code>{$row['accession_number']}</code></td>
                                                    <td><small class='text-muted'>$display_date</small></td>
                                                    <td>$status</td>
                                                </tr>";
                                        }
                                        ?>
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
                            <a href="issue-book.php" class="btn btn-primary btn-block py-2 mb-3">
                                <i class="fas fa-plus-circle mr-2"></i> Issue Book
                            </a>
                            <a href="view-issued-books.php" class="btn btn-warning btn-block py-2 mb-3 text-white font-weight-bold">
                                <i class="fas fa-hand-holding mr-2"></i> Issued Books
                            </a>
                            <a href="add-book.php" class="btn btn-success btn-block py-2 mb-3">
                                <i class="fas fa-book mr-2"></i> Add Books
                            </a>
                            <a href="manage-books.php" class="btn btn-info btn-block py-2">
                                <i class="fas fa-list mr-2"></i> View Catalog
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>