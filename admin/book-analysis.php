<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

// STRICT ADMIN ACCESS ONLY
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('Access Denied. Admins Only.'); window.location.href='dashboard.php';</script>";
    exit();
}

// 1. FETCH OFFICIAL BRANCHES FROM SETTINGS
$branch_settings = mysqli_query($connection, "SELECT config_value FROM settings WHERE config_key = 'branches' OR config_key = 'branch' LIMIT 1");
$branch_row = mysqli_fetch_assoc($branch_settings);
$official_branches = ($branch_row) ? explode(',', $branch_row['config_value']) : [];

// 2. FETCH OTHER FILTER OPTIONS
$authors_res    = mysqli_query($connection, "SELECT DISTINCT author FROM books WHERE is_deleted = 0 ORDER BY author ASC");
$publishers_res = mysqli_query($connection, "SELECT DISTINCT publisher FROM books WHERE is_deleted = 0 ORDER BY publisher ASC");
$editions_res   = mysqli_query($connection, "SELECT DISTINCT edition FROM books WHERE edition != '' AND is_deleted = 0 ORDER BY edition ASC");

// 3. MULTI-FILTER LOGIC
$where_clauses = ["is_deleted = 0"];

if (!empty($_GET['f_branch'])) {
    $val = mysqli_real_escape_string($connection, $_GET['f_branch']);
    $where_clauses[] = "branch = '$val'";
}
if (!empty($_GET['f_author'])) {
    $val = mysqli_real_escape_string($connection, $_GET['f_author']);
    $where_clauses[] = "author = '$val'";
}
if (!empty($_GET['f_publisher'])) {
    $val = mysqli_real_escape_string($connection, $_GET['f_publisher']);
    $where_clauses[] = "publisher = '$val'";
}
if (!empty($_GET['f_edition'])) {
    $val = mysqli_real_escape_string($connection, $_GET['f_edition']);
    $where_clauses[] = "edition = '$val'";
}

$where_sql = " WHERE " . implode(" AND ", $where_clauses);

// 4. FETCH DATA & STATS
$books_result = mysqli_query($connection, "SELECT * FROM books $where_sql ORDER BY accession_number DESC");
$stats = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as total_qty, COUNT(DISTINCT title) as unique_titles, SUM(price) as total_val FROM books $where_sql"));
?>

<style>
    /* UI Structure */
    html, body { height: 100vh; overflow: hidden; background-color: #f4f7f6; }
    .main-wrapper { display: flex; height: 100vh; width: 100vw; }
    
    .content-area { 
        flex-grow: 1; 
        display: flex; 
        flex-direction: column; 
        overflow: hidden; 
        margin-left: 240px; /* Aligned with sidebar */
    }

    .sticky-header { 
        background: white; 
        border-bottom: 2px solid #eee; 
        padding: 15px 30px; 
        flex-shrink: 0; 
        z-index: 1000;
    }

    .filter-section {
        background: #fff;
        padding: 15px 30px;
        border-bottom: 1px solid #ddd;
        flex-shrink: 0;
        z-index: 999;
    }

    .scroll-container { 
        padding: 20px 30px 100px 30px; 
        flex-grow: 1; 
        overflow-y: auto; 
        position: relative;
    }

    /* Sticky Table Header Logic */
    .analysis-table { background: white; border-radius: 8px; border-collapse: separate; }
    
    #analysisTable thead th { 
        position: sticky; 
        top: 0; 
        background-color: #f8f9fa; 
        z-index: 10; 
        box-shadow: inset 0 -1px 0 #dee2e6;
        border-top: 0;
        padding: 12px 15px;
    }
    
    .table td { font-weight: normal; font-size: 0.9rem; vertical-align: middle; }
    .label-text { font-size: 0.75rem; color: #777; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; }
    
    .stat-inline { border-left: 1px solid #ddd; padding-left: 15px; margin-left: 15px; }
</style>

<div class="main-wrapper">
    <?php include "../sidebar.php"; ?>

    <div class="content-area">
        <div class="sticky-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <h4 class="m-0 font-weight-bold mr-3">
                    <i class="fas fa-chart-line text-success mr-2"></i> Analysis
                </h4>
                <div class="stat-inline">
                    <span class="label-text">Total Copies:</span>
                    <span class="font-weight-bold ml-1"><?php echo $stats['total_qty'] ?? 0; ?></span>
                </div>
                <div class="stat-inline">
                    <span class="label-text">Unique Titles:</span>
                    <span class="font-weight-bold ml-1 text-info"><?php echo $stats['unique_titles'] ?? 0; ?></span>
                </div>
            </div>
            <div class="text-right">
                <span class="label-text d-block">Total Asset Value</span>
                <span class="h5 m-0 font-weight-bold text-success">₹<?php echo number_format($stats['total_val'] ?? 0, 2); ?></span>
            </div>
        </div>

        <div class="filter-section">
            <form method="GET" class="row">
                <div class="col-md-2">
                    <label class="label-text">Branch</label>
                    <select name="f_branch" class="form-control form-control-sm">
                        <option value="">All Branches</option>
                        <?php foreach($official_branches as $b): 
                            $b = trim($b);
                            if(empty($b)) continue;
                            $sel = (isset($_GET['f_branch']) && $_GET['f_branch'] == $b) ? 'selected' : '';
                            echo "<option value='$b' $sel>$b</option>";
                        endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="label-text">Author</label>
                    <select name="f_author" class="form-control form-control-sm">
                        <option value="">All Authors</option>
                        <?php while($a = mysqli_fetch_assoc($authors_res)) {
                            $sel = (isset($_GET['f_author']) && $_GET['f_author'] == $a['author']) ? 'selected' : '';
                            echo "<option value='{$a['author']}' $sel>{$a['author']}</option>";
                        } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="label-text">Publisher</label>
                    <select name="f_publisher" class="form-control form-control-sm">
                        <option value="">All Publishers</option>
                        <?php while($p = mysqli_fetch_assoc($publishers_res)) {
                            $sel = (isset($_GET['f_publisher']) && $_GET['f_publisher'] == $p['publisher']) ? 'selected' : '';
                            echo "<option value='{$p['publisher']}' $sel>{$p['publisher']}</option>";
                        } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="label-text">Edition</label>
                    <select name="f_edition" class="form-control form-control-sm">
                        <option value="">All Editions</option>
                        <?php while($e = mysqli_fetch_assoc($editions_res)) {
                            $sel = (isset($_GET['f_edition']) && $_GET['f_edition'] == $e['edition']) ? 'selected' : '';
                            echo "<option value='{$e['edition']}' $sel>{$e['edition']}</option>";
                        } ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-success btn-sm px-4 mr-2">Filter Data</button>
                    <a href="book-analysis.php" class="btn btn-light btn-sm px-3 border">Reset</a>
                </div>
            </form>
        </div>

        <div class="scroll-container">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0" id="analysisTable">
                        <thead>
                            <tr class="label-text">
                                <th class="pl-4">Acc No.</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Edition</th>
                                <th>Branch</th>
                                <th>Publisher</th>
                                <th class="text-right pr-4">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($books_result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($books_result)): ?>
                                <tr>
                                    <td class="pl-4 text-success font-weight-bold"><code><?php echo $row['accession_number']; ?></code></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['author']); ?></td>
                                    <td><?php echo $row['edition'] ?: '-'; ?></td>
                                    <td><span class="badge badge-light text-muted border"><?php echo $row['branch'] ?: 'N/A'; ?></span></td>
                                    <td><?php echo htmlspecialchars($row['publisher']); ?></td>
                                    <td class="text-right pr-4">₹<?php echo number_format($row['price'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted">No books match these criteria.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>