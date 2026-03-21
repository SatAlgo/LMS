<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// 1. Handle Date Filters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Default to start of month
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// 2. Fetch Fine Settings
$set_res = mysqli_query($connection, "SELECT config_key, config_value FROM settings WHERE config_key IN ('fine_rate', 'fine_rate_late')");
$settings = [];
while($s = mysqli_fetch_assoc($set_res)) { $settings[$s['config_key']] = $s['config_value']; }
$fine_low = $settings['fine_rate'] ?? 2;
$fine_high = $settings['fine_rate_late'] ?? 5;

// 3. STATS QUERIES
// A. Total Fines Collected (Based on Filter)
$collected_res = mysqli_query($connection, "SELECT SUM(fine) as total FROM issued_books WHERE status = 0 AND return_date BETWEEN '$start_date' AND '$end_date'");
$total_collected = mysqli_fetch_assoc($collected_res)['total'] ?? 0;

// B. Active Overdue Books (Not yet returned)
$overdue_res = mysqli_query($connection, "SELECT ib.* FROM issued_books ib WHERE ib.status = 1 AND ib.due_date < CURDATE()");
$total_projected_fine = 0;
$overdue_count = mysqli_num_rows($overdue_res);
while($ob = mysqli_fetch_assoc($overdue_res)) {
    $days = floor((strtotime(date("Y-m-d")) - strtotime($ob['due_date'])) / 86400);
    $f = ($days <= 15) ? ($days * $fine_low) : ((15 * $fine_low) + (($days - 15) * $fine_high));
    $total_projected_fine += $f;
}

// C. Monthly Collection Stats (Last 6 Months)
$monthly_stats = mysqli_query($connection, "SELECT DATE_FORMAT(return_date, '%b') as month, SUM(fine) as total 
    FROM issued_books WHERE status = 0 AND return_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month ORDER BY return_date ASC");
?>

<style>
    html, body { height: 100vh; overflow: hidden; background-color: #f4f7f6; }
    .main-wrapper { display: flex; height: 100vh; width: 100vw; }
    .content-area { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; margin-left: 10px; }
    .sticky-header { background: white; border-bottom: 2px solid #eee; padding: 15px 30px; margin-left: 230px; flex-shrink: 0; }
    
    /* Scrollable Layout */
    .main-content { padding: 10px; flex-grow: 1; overflow-y: auto; }
    .scroll-card { max-height: 350px; overflow-y: auto; }

    /* Modern Color Palette */
    .bg-revenue { background: #e8f5e9; color: #2e7d32; }
    .bg-pending { background: #fff3e0; color: #ef6c00; }
    .bg-overdue { background: #ffebee; color: #c62828; }
    
    .stat-card { border: none; border-radius: 15px; }
    .icon-box { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }

    /* Simple CSS Bar Chart */
    .bar-container { display: flex; align-items: flex-end; gap: 10px; height: 100px; padding-top: 10px; }
    .bar { flex: 1; background: #28a745; border-radius: 4px 4px 0 0; min-height: 5px; position: relative; }
    .bar:hover { background: #218838; }
    .bar-label { font-size: 10px; text-align: center; margin-top: 5px; color: #666; }
</style>

<div class="main-wrapper">
    <?php include "../sidebar.php"; ?>

    <div class="content-area">
        <div class="sticky-header d-flex justify-content-between align-items-center">
            <h3 class="m-0 font-weight-bold text-dark"><i class="fas fa-chart-pie text-success mr-2"></i> Collection Dashboard</h3>
            
            <form class="form-inline bg-light p-2 rounded shadow-sm">
                <input type="date" name="start_date" class="form-control form-control-sm mr-2" value="<?php echo $start_date; ?>">
                <input type="date" name="end_date" class="form-control form-control-sm mr-2" value="<?php echo $end_date; ?>">
                <button type="submit" class="btn btn-sm btn-success px-3 font-weight-bold">Filter</button>
            </form>
        </div>

        <div class="main-content">
            <div class="row mb-2">
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon-box bg-revenue mr-3"><i class="fas fa-check-double fa-lg"></i></div>
                            <div>
                                <h3 class="mb-0 font-weight-bold text-dark">₹<?php echo number_format($total_collected); ?></h3>
                                <small class="text-muted font-weight-bold text-uppercase">Collected Revenue</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon-box bg-pending mr-3"><i class="fas fa-hand-holding-usd fa-lg"></i></div>
                            <div>
                                <h3 class="mb-0 font-weight-bold text-dark">₹<?php echo number_format($total_projected_fine); ?></h3>
                                <small class="text-muted font-weight-bold text-uppercase">Pending Fines</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon-box bg-overdue mr-3"><i class="fas fa-calendar-times fa-lg"></i></div>
                            <div>
                                <h3 class="mb-0 font-weight-bold text-dark"><?php echo $overdue_count; ?></h3>
                                <small class="text-muted font-weight-bold text-uppercase">Overdue Assets</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-7">
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
                        <div class="card-body">
                            <h6 class="font-weight-bold mb-4">Collection Trend (Last 6 Months)</h6>
                            <div class="bar-container">
                                <?php 
                                $months = [];
                                $totals = [];
                                while($m = mysqli_fetch_assoc($monthly_stats)) {
                                    $months[] = $m['month'];
                                    $totals[] = $m['total'];
                                }
                                $max = (max($totals) > 0) ? max($totals) : 1;
                                foreach($months as $idx => $month_name):
                                    $height = ($totals[$idx] / $max) * 100;
                                ?>
                                <div style="flex:1; display:flex; flex-direction:column; align-items:center;">
                                    <div class="bar" style="height: <?php echo $height; ?>%; width: 100%;" title="₹<?php echo $totals[$idx]; ?>"></div>
                                    <div class="bar-label"><?php echo $month_name; ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                        <div class="card-header bg-white font-weight-bold border-0 pt-3">Revenue by Branch</div>
                        <div class="card-body scroll-card p-0">
                            <table class="table table-hover">
                                <thead class="thead-light small uppercase">
                                    <tr><th>Branch</th><th class="text-right">Collection</th></tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $branch_stats = mysqli_query($connection, "SELECT u.branch, SUM(ib.fine) as revenue FROM issued_books ib JOIN users u ON ib.prn = u.prn WHERE ib.status = 0 GROUP BY u.branch ORDER BY revenue DESC");
                                    while($row = mysqli_fetch_assoc($branch_stats)): ?>
                                    <tr>
                                        <td><b><?php echo $row['branch']; ?></b></td>
                                        <td class="text-right text-success font-weight-bold">₹<?php echo number_format($row['revenue']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                        <div class="card-header bg-white font-weight-bold border-0 pt-3">Recent Transactions</div>
                        <div class="card-body scroll-card p-0">
                            <div class="list-group list-group-flush">
                                <?php
                                $recent = mysqli_query($connection, "SELECT ib.fine, u.name, ib.return_date, b.title 
                                    FROM issued_books ib 
                                    JOIN users u ON ib.prn = u.prn 
                                    JOIN books b ON ib.accession_number = b.accession_number
                                    WHERE ib.status = 0 AND ib.fine > 0 
                                    ORDER BY ib.return_date DESC LIMIT 15");
                                while($r = mysqli_fetch_assoc($recent)): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <div style="max-width: 70%;">
                                        <h6 class="mb-0 font-weight-bold small"><?php echo $r['name']; ?></h6>
                                        <p class="mb-0 text-muted" style="font-size: 11px;"><?php echo $r['title']; ?></p>
                                    </div>
                                    <div class="text-right">
                                        <div class="badge badge-success px-2 py-1">₹<?php echo $r['fine']; ?></div>
                                        <div class="text-muted mt-1" style="font-size: 10px;"><?php echo date('d M', strtotime($r['return_date'])); ?></div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>