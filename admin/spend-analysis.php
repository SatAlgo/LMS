<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$selected_year = $_GET['year'] ?? date('Y');

// Core Metrics
$total_inv_res = mysqli_query($connection, "SELECT SUM(price) as total FROM books WHERE is_deleted = 0");
$lifetime_investment = mysqli_fetch_assoc($total_inv_res)['total'] ?? 0;

$year_inv_res = mysqli_query($connection, "SELECT SUM(price) as total FROM books WHERE YEAR(entry_date) = '$selected_year' AND is_deleted = 0");
$year_investment = mysqli_fetch_assoc($year_inv_res)['total'] ?? 0;

// Monthly Trend
$monthly_spend = mysqli_query($connection, "SELECT MONTHNAME(entry_date) as mname, SUM(price) as total 
    FROM books WHERE YEAR(entry_date) = '$selected_year' AND is_deleted = 0 
    GROUP BY mname");

// Yearly Trend
$yearly_trend = mysqli_query($connection, "SELECT YEAR(entry_date) as year, SUM(price) as total, COUNT(accession_number) as qty
    FROM books WHERE is_deleted = 0 GROUP BY year ORDER BY year DESC LIMIT 10");

// Detail query for live search
$category_spend = mysqli_query($connection, "SELECT accession_number, title, price FROM books WHERE is_deleted = 0 ORDER BY accession_number DESC");
?>

<style>
    /* Full Page Fixed Layout */
    html, body { height: 100vh; overflow: hidden; background-color: #f4f7f6; margin: 0; }
    .main-wrapper { display: flex; height: 100vh; width: 100vw; overflow: hidden; }
    .content-area { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; margin-left: 10px; }
    .sticky-header { background: white; border-bottom: 2px solid #eee; padding: 15px 30px; margin-left: 230px; flex-shrink: 0; }
    .main-content { padding: 25px; flex-grow: 1; overflow: hidden; display: flex; flex-direction: column; gap: 20px; }

    .data-row { flex-grow: 1; display: flex; gap: 20px; overflow: hidden; }
    .left-col-scroll { flex: 2; overflow-y: auto; padding-right: 10px; display: flex; flex-direction: column; gap: 20px; }
    .right-col-fixed { flex: 1; display: flex; flex-direction: column; overflow: hidden; }

    .spend-card { border: none; border-radius: 15px; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .bg-gradient-blue { background: linear-gradient(45deg, #141D49, #1e2b6d); color: white; }
    .bg-gradient-purple { background: linear-gradient(45deg, #6610f2, #520dc2); color: white; }

    /* Chart styles */
    .chart-wrapper { display: flex; gap: 15px; align-items: stretch; height: 160px; }
    .y-axis-labels { display: flex; flex-direction: column; justify-content: space-between; padding-bottom: 22px; text-align: right; min-width: 50px; }
    .y-label { font-size: 10px; font-weight: bold; color: #999; }
    .chart-container { flex-grow: 1; display: flex; align-items: flex-end; justify-content: space-between; gap: 8px; border-bottom: 2px solid #eee; border-left: 2px solid #eee; padding-left: 5px; }
    .bar-group { flex: 1; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; height: 100%; }
    .bar { width: 80%; background: #6610f2; border-radius: 4px 4px 0 0; transition: height 0.3s ease; }
    .bar:hover { filter: brightness(1.2); cursor: pointer; }
    .bar-label { font-size: 10px; margin-top: 5px; font-weight: bold; color: #777; }

    /* Search & Filter UI */
    .filter-section { background: #fff; border-radius: 15px; border: 1px solid #dee2e6; padding: 20px; }
    .search-row { display: none; } /* Hidden by default until search matches */
    .filter-sticky {
        position: sticky;
        top: 0;
        z-index: 110; /* Higher than table headers but lower than sidebar */
        background: #f4f7f6; /* Match page background to prevent overlapping text */
        padding-bottom: 5px; /* Spacing between sticky bar and table */
    }

    /* Ensure the table header sticks below the filter section */
    .sticky-table thead th {
        position: sticky;
        top: 140px;                 /* 👈 SAME AS FILTER HEIGHT */
        background: #f8f9fa;
        z-index: 105;               /* slightly below filter (110) */
        padding: 15px 20px;
        border-bottom: 2px solid #dee2e6;
    }
    .sticky-table tbody td { padding: 12px 20px; border-bottom: 1px solid #eee; }
</style>

<div class="main-wrapper">
    <?php include "../sidebar.php"; ?>

    <div class="content-area">
        <div class="sticky-header d-flex justify-content-between align-items-center">
            <h4 class="m-0 font-weight-bold text-dark"><i class="fas fa-coins text-primary mr-2"></i> Spending Analysis</h4>
            <form class="form-inline">
                <select name="year" class="form-control form-control-sm" onchange="this.form.submit()">
                    <?php 
                    $years_q = mysqli_query($connection, "SELECT DISTINCT YEAR(entry_date) as y FROM books ORDER BY y DESC");
                    while($y = mysqli_fetch_assoc($years_q)): ?>
                        <option value="<?= $y['y']; ?>" <?= ($selected_year == $y['y']) ? 'selected' : ''; ?>>Year <?= $y['y']; ?></option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>

        <div class="main-content">
            <div class="row metrics-row">
                <div class="col-md-6"><div class="card spend-card bg-gradient-blue p-3">
                    <small class="uppercase font-weight-bold opacity-8 text-white">Total Asset Value</small>
                    <h2 class="font-weight-bold mb-0 text-white">₹<?= number_format($lifetime_investment, 2); ?></h2>
                </div></div>
                <div class="col-md-6"><div class="card spend-card bg-gradient-purple p-3">
                    <small class="uppercase font-weight-bold opacity-8 text-white">Budget Utilized (<?= $selected_year; ?>)</small>
                    <h2 class="font-weight-bold mb-0 text-white">₹<?= number_format($year_investment, 2); ?></h2>
                </div></div>
            </div>

            <div class="data-row">
                <div class="left-col-scroll">
                    <div class="card spend-card p-4">
                        <h6 class="font-weight-bold mb-4 text-dark">Procurement Trend</h6>
                        <div class="chart-wrapper">
                            <div class="y-axis-labels">
                                <?php 
                                $m_data = [];
                                mysqli_data_seek($monthly_spend, 0);
                                while($row = mysqli_fetch_assoc($monthly_spend)) { $m_data[$row['mname']] = $row['total']; }
                                $all_months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                                $max_m = (count($m_data) > 0) ? max($m_data) : 1;
                                $steps = 4;
                                for ($i = $steps; $i >= 0; $i--) {
                                    $val = ($max_m / $steps) * $i;
                                    $display_val = ($val >= 1000) ? '₹' . round($val/1000, 1) . 'k' : '₹' . round($val);
                                    echo "<span class='y-label'>$display_val</span>";
                                }
                                ?>
                            </div>
                            <div class="chart-container">
                                <?php foreach($all_months as $m_short):
                                    $full_m = date("F", strtotime("2020-$m_short-01"));
                                    $val = $m_data[$full_m] ?? 0;
                                    $perc = ($val / $max_m) * 100;
                                ?>
                                <div class="bar-group">
                                    <div class="bar" style="height: <?= $perc; ?>%;" title="<?= $full_m; ?>: ₹<?= number_format($val); ?>"></div>
                                    <div class="bar-label"><?= $m_short; ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="filter-sticky">
                        <div class="filter-section shadow-sm">
                            <div class="row">
                                <div class="col-md-7">
                                    <label class="small font-weight-bold text-muted">LIVE PRICE LOOKUP (TITLE / ACC NO)</label>
                                    <input type="text" id="masterSearch" class="form-control" placeholder="Search any book to see its cost...">
                                </div>
                                <div class="col-md-5">
                                    <label class="small font-weight-bold text-muted">PRICE RANGE ANALYSIS</label>
                                    <select id="priceFilter" class="form-control">
                                        <option value="none">Choose Range...</option>
                                        <option value="0-500">Below ₹500</option>
                                        <option value="501-1000">₹501 - ₹1000</option>
                                        <option value="1001-5000">₹1001 - ₹5000</option>
                                        <option value="5001">Above ₹5000</option>
                                    </select>
                                </div>
                            </div>
                            <div id="statLabel" class="mt-2 small font-weight-bold text-primary" style="display:none;"></div>
                        </div>
                    </div>

                    <div class="card spend-card table-card">
                        <table class="sticky-table" id="spendTable">
                            <thead>
                                <tr>
                                    <th style="width: 120px;">Acc No</th>
                                    <th>Book Title</th>
                                    <th class="text-right" style="width: 140px;">Price</th>
                                </tr>
                            </thead>
                            <tbody id="resultBody">
                                <?php while($row = mysqli_fetch_assoc($category_spend)): ?>
                                <tr class="search-row" data-price="<?= $row['price']; ?>">
                                    <td><code><?= $row['accession_number']; ?></code></td>
                                    <td class="font-weight-bold small text-dark"><?= htmlspecialchars($row['title']); ?></td>
                                    <td class="text-right font-weight-bold text-primary">₹<?= number_format($row['price']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <tr id="noResults"><td colspan="3" class="text-center py-5 text-muted">No books currently visible. Type or Filter to start.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="right-col-fixed">
                    <div class="card spend-card h-100 d-flex flex-column">
                        <div class="card-header bg-white font-weight-bold border-0 pt-3">Yearly Spending</div>
                        <div class="list-group list-group-flush overflow-auto">
                            <?php mysqli_data_seek($yearly_trend, 0); while($y = mysqli_fetch_assoc($yearly_trend)): ?>
                            <div class="list-group-item border-0 py-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="font-weight-bold"><?= $y['year']; ?></span>
                                    <span class="text-primary font-weight-bold small">₹<?= number_format($y['total']); ?></span>
                                </div>
                                <small class="text-muted"><?= $y['qty']; ?> items.</small>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>

<script>
$(document).ready(function(){
    function filterResults() {
        let q = $("#masterSearch").val().toLowerCase().trim();
        let range = $("#priceFilter").val();
        let matchCount = 0;
        let totalPrice = 0;

        if (q === "" && range === "none") {
            $(".search-row").hide();
            $("#noResults").show();
            $("#statLabel").hide();
            return;
        }

        $("#noResults").hide();

        $(".search-row").each(function() {
            let acc = $(this).find('td:first').text().toLowerCase();
            let title = $(this).find('td:nth-child(2)').text().toLowerCase();
            let price = parseFloat($(this).data('price'));
            
            let matchesText = (q === "" || title.includes(q) || acc.includes(q));
            let matchesRange = false;

            if (range === "none") matchesRange = true;
            else if (range === "0-500" && price <= 500) matchesRange = true;
            else if (range === "501-1000" && price > 500 && price <= 1000) matchesRange = true;
            else if (range === "1001-5000" && price > 1000 && price <= 5000) matchesRange = true;
            else if (range === "5001" && price > 5000) matchesRange = true;

            if (matchesText && matchesRange) {
                $(this).show();
                matchCount++;
                totalPrice += price;
            } else {
                $(this).hide();
            }
        });

        if (matchCount > 0) {
            $("#statLabel").text("Showing " + matchCount + " books found (Total Range Value: ₹" + totalPrice.toLocaleString() + ")").show();
        } else {
            $("#statLabel").hide();
            $("#noResults").show();
        }
    }

    $("#masterSearch").on("keyup", filterResults);
    $("#priceFilter").on("change", filterResults);
});
</script>