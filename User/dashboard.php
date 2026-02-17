<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$prn = $_SESSION['prn'];

// 1. Fetch Student Details
$user_query = mysqli_query($connection, "SELECT * FROM users WHERE prn = '$prn'");
$user_data = mysqli_fetch_assoc($user_query);

// 2. Fetch Fine Rates from Settings
$set_res = mysqli_query($connection, "SELECT config_key, config_value FROM settings WHERE config_key IN ('fine_rate', 'fine_rate_late')");
$settings = [];
while($s = mysqli_fetch_assoc($set_res)) { $settings[$s['config_key']] = $s['config_value']; }
$fine_low = $settings['fine_rate'] ?? 2;
$fine_high = $settings['fine_rate_late'] ?? 5;

// 3. Fetch All Books (Both Active and Returned)
$all_books_query = "SELECT ib.*, b.title, b.author 
                    FROM issued_books ib 
                    JOIN books b ON ib.accession_number = b.accession_number 
                    WHERE ib.prn = '$prn' 
                    ORDER BY ib.status DESC, ib.issue_date DESC";
$all_books_res = mysqli_query($connection, $all_books_query);
?>

<style>
    /* Full Page - No Scroll */
    html, body { height: 100vh; overflow: hidden; background-color: #f4f7f6; margin: 0; }
    .main-wrapper { display: flex; height: 100vh; width: 100vw; flex-direction: column; }

    /* Content Area Split */
    .main-content { padding: 30px 20px; flex-grow: 1; overflow: hidden; display: flex; gap: 30px; }

    /* Left Side: Profile (Static) */
    .profile-section { width: 320px; flex-shrink: 0; }
    .user-card { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }

    /* Right Side: Books (Scrollable) */
    .books-section { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; }
    .scrollable-list { flex-grow: 1; overflow-y: auto; padding-right: 15px; }
    
    .book-item { background: white; border-radius: 12px; border: none; margin-bottom: 15px; transition: 0.2s; border-left: 5px solid #ccc; }
    .book-item.active-book { border-left-color: #007bff; }
    .book-item.returned-book { border-left-color: #28a745; opacity: 0.9; }

    .stat-label { font-size: 11px; text-uppercase; font-weight: bold; color: #888; display: block; }
    .stat-value { font-size: 14px; font-weight: 600; color: #333; }
    
    /* Custom Scrollbar */
    .scrollable-list::-webkit-scrollbar { width: 6px; }
    .scrollable-list::-webkit-scrollbar-track { background: #f1f1f1; }
    .scrollable-list::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
</style>

<div class="main-wrapper">

    <div class="main-content">
        <div class="profile-section">
            <div class="card user-card p-4">
                <div class="text-center mb-4">
                    <div class="bg-light rounded-circle d-inline-block p-3 mb-3" style="width: 100px; height: 100px;">
                        <i class="fas fa-user-graduate fa-4x text-primary"></i>
                    </div>
                    <h5 class="font-weight-bold mb-1"><?php echo $user_data['name']; ?></h5>
                    <span class="badge badge-info px-3 py-2"><?php echo $user_data['branch']; ?></span>
                </div>
                
                <div class="border-top pt-3">
                    <div class="mb-3">
                        <span class="stat-label">PRN Number</span>
                        <span class="stat-value"><?php echo $user_data['prn']; ?></span>
                    </div>
                    <div class="mb-3">
                        <span class="stat-label">Library Card ID</span>
                        <span class="stat-value"><?php echo $user_data['library_card_no']; ?></span>
                    </div>
                    <div class="mb-4">
                        <span class="stat-label">Current Division</span>
                        <span class="stat-value">Division <?php echo $user_data['division']; ?></span>
                    </div>
                    
                    <a href="search-books.php" class="btn btn-primary btn-block py-2 font-weight-bold shadow-sm mb-2">
                        <i class="fas fa-book mr-2"></i> BROWSE CATALOG
                    </a>
                </div>
            </div>
        </div>

        <div class="books-section">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <h5 class="font-weight-bold m-0 text-dark">Your Book Transactions</h5>
                <?php 
                    $active_count = mysqli_query($connection, "SELECT COUNT(*) as total FROM issued_books WHERE prn = '$prn' AND status = 1");
                    $active_total = mysqli_fetch_assoc($active_count)['total'];
                ?>
                <span class="text-muted small">
                    Active: <b><?= $active_total ?></b> | Total History: <b><?= mysqli_num_rows($all_books_res) ?></b>
                </span>
            </div>

            <div class="scrollable-list">
                <?php if(mysqli_num_rows($all_books_res) > 0): ?>
                    <?php while($book = mysqli_fetch_assoc($all_books_res)): 
                        $is_returned = ($book['status'] == 0);
                        $issue_date = strtotime($book['issue_date']);
                        $due_date = strtotime($book['due_date']);
                        
                        if($is_returned) {
                            $end_date = strtotime($book['return_date']);
                            $status_label = "Returned";
                            $status_class = "badge-success";
                            $card_class = "returned-book";
                        } else {
                            $end_date = strtotime(date("Y-m-d"));
                            $status_label = "Currently Issued";
                            $status_class = "badge-primary";
                            $card_class = "active-book";
                        }

                        // Calculate Duration
                        $duration = floor(($end_date - $issue_date) / 86400);
                        $overdue_days = ($end_date > $due_date) ? floor(($end_date - $due_date) / 86400) : 0;
                        
                        // Fine Calculation
                        if($is_returned) {
                            $fine_display = $book['fine']; // Use recorded fine from DB
                        } else {
                            if($overdue_days <= 0) $fine_display = 0;
                            elseif($overdue_days <= 15) $fine_display = $overdue_days * $fine_low;
                            else $fine_display = (15 * $fine_low) + (($overdue_days - 15) * $fine_high);
                        }
                    ?>
                        <div class="card book-item <?php echo $card_class; ?> shadow-sm">
                            <div class="card-body py-3">
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <span class="badge <?php echo $status_class; ?> mb-2"><?php echo $status_label; ?></span>
                                        <h6 class="font-weight-bold mb-1 text-dark"><?php echo htmlspecialchars($book['title']); ?></h6>
                                        <small class="text-muted">Acc No: <?php echo $book['accession_number']; ?> | Author: <?php echo htmlspecialchars($book['author']); ?></small>
                                    </div>
                                    
                                    <div class="col-md-2 text-center border-left">
                                        <span class="stat-label">Issue Date</span>
                                        <span class="stat-value"><?php echo date('d M, Y', $issue_date); ?></span>
                                    </div>

                                    <div class="col-md-2 text-center border-left">
                                        <span class="stat-label">Due Date</span>
                                        <span class="stat-value text-danger"><?php echo date('d M, Y', $due_date); ?></span>
                                    </div>

                                    <div class="col-md-2 text-center border-left">
                                        <span class="stat-label"><?php echo $is_returned ? 'Return Date' : 'Days Kept'; ?></span>
                                        <span class="stat-value">
                                            <?php echo $is_returned ? date('d M, Y', $end_date) : "$duration Days"; ?>
                                        </span>
                                    </div>

                                    <div class="col-md-2 text-right border-left">
                                        <span class="stat-label"><?php echo $is_returned ? 'Fine Paid' : 'Current Fine'; ?></span>
                                        <span class="stat-value <?php echo ($fine_display > 0) ? 'text-danger' : 'text-success'; ?>">
                                            ₹<?php echo $fine_display; ?>
                                        </span>
                                        <?php if(!$is_returned && $overdue_days > 0): ?>
                                            <br><small class="text-danger font-weight-bold"><?php echo $overdue_days; ?> days late</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center p-5 bg-white rounded shadow-sm">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No transaction history found.</h5>
                        <p class="small text-muted">Books you borrow will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>