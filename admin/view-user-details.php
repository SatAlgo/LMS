<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header("Location: ../index.php");
    exit();
}

$prn = mysqli_real_escape_string($connection, $_GET['prn']);
$user = mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM users WHERE prn = '$prn'"));

if (!$user) {
    header("Location: manage-users.php?msg=not_found");
    exit();
}

$set_res = mysqli_query($connection, "SELECT config_key, config_value FROM settings WHERE config_key IN ('fine_rate', 'fine_rate_late')");
$settings = [];
while($s = mysqli_fetch_assoc($set_res)) { $settings[$s['config_key']] = $s['config_value']; }
$fine_low = $settings['fine_rate'] ?? 2;
$fine_high = $settings['fine_rate_late'] ?? 5;

$book_result = mysqli_query($connection, "SELECT ib.*, b.title, b.author FROM issued_books ib JOIN books b ON ib.accession_number = b.accession_number WHERE ib.prn = '$prn' AND ib.status = 1");
$history_result = mysqli_query($connection, "SELECT ib.*, b.title, b.author FROM issued_books ib JOIN books b ON ib.accession_number = b.accession_number WHERE ib.prn = '$prn' AND ib.status = 0 ORDER BY ib.return_date DESC");
?>

<style>
    html, body { height: 100vh; overflow: hidden; background-color: #f4f7f6; }
    .main-wrapper { display: flex; height: 100vh; width: 100vw; }
    .content-area { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; }
    .sticky-header { background: white; border-bottom: 2px solid #eee; padding: 15px 30px; margin-left: 230px; flex-shrink: 0; z-index: 1000; }
    .main-content { padding: 16px 25px 100px 25px; flex-grow: 1; overflow-y: auto; scroll-behavior: smooth; }
    
    /* THE STICKY TABLE HEADER LOGIC */
    .table-responsive { 
        max-height: 400px; /* Set a limit so headers can stick within the card */
        overflow-y: auto; 
        border-radius: 8px;
    }

    .table thead th { 
        position: sticky; 
        top: 0; 
        background-color: #f8f9fa !important; 
        z-index: 2; 
        box-shadow: inset 0 -1px 0 #dee2e6; /* Prevents "gap" look while scrolling */
        border-top: none !important;
    }

    /* Professional UI Adjustments */
    .profile-card, .table-card { border: none; border-radius: 15px; background: white; margin-bottom: 25px; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
    .section-title { font-size: 0.85rem; font-weight: 800; text-transform: uppercase; color: #adb5bd; letter-spacing: 1.2px; margin-bottom: 20px; display: flex; align-items: center; }
    .section-title::after { content: ""; flex: 1; height: 1px; background: #eee; margin-left: 15px; }
    .data-label { font-weight: 600; color: #6c757d; width: 140px; font-size: 0.95rem; }
    .data-value { color: #212529; font-size: 0.95rem; }
</style>

<div class="main-wrapper">
    <?php include "../sidebar.php"; ?>

    <div class="content-area">
        <div class="sticky-header d-flex justify-content-between align-items-center">
            <h3 class="m-0 font-weight-bold text-dark"><i class="fas fa-id-card text-primary mr-2"></i> Student Dossier</h3>
            <div>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-warning mr-2"><i class="fas fa-edit"></i> Edit Profile</a>
                <?php endif; ?>
                <a href="manage-users.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>

        <div class="main-content">
            <div class="card profile-card p-4">
                <div class="section-title">Personal Information</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex mb-3"><div class="data-label">Name</div><div class="data-value"><?php echo htmlspecialchars($user['name']); ?></div></div>
                        <div class="d-flex mb-3"><div class="data-label">PRN</div><div class="data-value"><code><?php echo htmlspecialchars($user['prn']); ?></code></div></div>
                        <div class="d-flex mb-3"><div class="data-label">Email</div><div class="data-value"><?php echo htmlspecialchars($user['email']); ?></div></div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex mb-3"><div class="data-label">Branch</div><div class="data-value"><?php echo htmlspecialchars($user['branch']); ?></div></div>
                        <div class="d-flex mb-3"><div class="data-label">Division</div><div class="data-value"><?php echo htmlspecialchars($user['division']); ?></div></div>
                        <div class="d-flex mb-3"><div class="data-label">Contact</div><div class="data-value"><?php echo htmlspecialchars($user['mobile']); ?></div></div>
                    </div>
                </div>
            </div>

            <div class="card table-card p-4">
                <div class="section-title text-primary">Active Library Assets</div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr class="small text-muted text-uppercase">
                                <th>Accession No</th>
                                <th>Book Title</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Live Fine</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($book_result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($book_result)): 
                                    $due = new DateTime($row['due_date']);
                                    $today = new DateTime();
                                    $live_fine = 0;
                                    if ($today > $due) {
                                        $days = $due->diff($today)->days;
                                        $live_fine = ($days <= 15) ? ($days * $fine_low) : ((15 * $fine_low) + (($days - 15) * $fine_high));
                                    }
                                ?>
                                <tr>
                                    <td class="align-middle"><code><?php echo $row['accession_number']; ?></code></td>
                                    <td class="align-middle font-weight-bold"><?php echo $row['title']; ?></td>
                                    <td class="align-middle"><?php echo date('d M Y', strtotime($row['issue_date'])); ?></td>
                                    <td class="align-middle"><span class="badge badge-light border text-danger"><?php echo date('d M Y', strtotime($row['due_date'])); ?></span></td>
                                    <td class="align-middle"><span class="badge badge-warning">₹<?php echo $live_fine; ?></span></td>
                                    <td class="text-center align-middle">
                                        <a href="return-book.php?auto_accession=<?php echo $row['accession_number']; ?>" class="btn btn-sm btn-success px-3 rounded-pill shadow-sm">
                                            Return
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">No books currently issued to this student.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card table-card p-4">
                <div class="section-title text-secondary">Borrowing History</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr class="small text-muted text-uppercase">
                                <th>Book Title</th>
                                <th>Issue Date</th>
                                <th>Return Date</th>
                                <th>Fine Paid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($history_result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($history_result)): ?>
                                <tr>
                                    <td class="align-middle font-weight-bold py-2"><?php echo $row['title']; ?></td>
                                    <td class="align-middle text-muted"><?php echo date('d M Y', strtotime($row['issue_date'])); ?></td>
                                    <td class="align-middle"><?php echo date('d M Y', strtotime($row['return_date'])); ?></td>
                                    <td class="align-middle"><span class="text-success font-weight-bold">₹<?php echo $row['fine']; ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No past transaction history found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>