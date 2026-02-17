<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../includes/PHPMailer/Exception.php';
require '../includes/PHPMailer/PHPMailer.php';
require '../includes/PHPMailer/SMTP.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header("Location: ../index.php");
    exit();
}

$set_res = mysqli_query($connection, "SELECT config_value FROM settings WHERE config_key = 'fine_rate'");
$fine_rate = mysqli_fetch_assoc($set_res)['config_value'] ?? 2;

function sendLibraryReminder($recipientEmail, $studentName, $bookTitle, $dueDate, $isOverdue, $fineAmount) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'algorithmseven@gmail.com';
        $mail->Password   = 'rafj opvi ncmb hnru'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('algorithmseven@gmail.com', 'MIT AOE Central Library');
        $mail->addAddress($recipientEmail);
        $mail->isHTML(true);
        $mail->Subject = $isOverdue ? "URGENT: Overdue Book Reminder" : "Upcoming Book Due Date";
        
        $body = "<h3>Library Reminder</h3><p>Dear $studentName,</p><p>The book <b>'$bookTitle'</b> is " . ($isOverdue ? "overdue (Due: $dueDate)." : "due on $dueDate.") . "</p><p><b>Fine Status: ₹$fineAmount</b></p><p>Regards,<br>Library Admin</p>";
        
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) { return false; }
}

if (isset($_GET['send_mail'])) {
    $issue_id = mysqli_real_escape_string($connection, $_GET['send_mail']);
    $q = mysqli_query($connection, "SELECT ib.*, b.title, u.name, u.email FROM issued_books ib JOIN books b ON ib.accession_number = b.accession_number JOIN users u ON ib.prn = u.prn WHERE ib.id = '$issue_id'");
    $data = mysqli_fetch_assoc($q);
    if ($data) {
        $today = new DateTime(); $due = new DateTime($data['due_date']); $overdue = ($today > $due);
        $fine = $overdue ? ($due->diff($today)->days * $fine_rate) : 0;
        if (sendLibraryReminder($data['email'], $data['name'], $data['title'], date('d M Y', strtotime($data['due_date'])), $overdue, $fine)) {
            $_SESSION['mail_msg'] = "Reminder sent to " . $data['name'];
        }
    }
    header("Location: view-issued-books.php"); exit();
}

if (isset($_POST['bulk_mail'])) {
    $q = mysqli_query($connection, "SELECT ib.*, b.title, u.name, u.email FROM issued_books ib JOIN books b ON ib.accession_number = b.accession_number JOIN users u ON ib.prn = u.prn WHERE ib.status = 1 AND ib.due_date < CURDATE()");
    $count = 0;
    while($row = mysqli_fetch_assoc($q)) {
        $due = new DateTime($row['due_date']); $fine = ($due->diff(new DateTime())->days * $fine_rate);
        if (sendLibraryReminder($row['email'], $row['name'], $row['title'], date('d M Y', strtotime($row['due_date'])), true, $fine)) { $count++; }
    }
    $_SESSION['mail_msg'] = "Bulk reminders sent to $count students.";
    header("Location: view-issued-books.php"); exit();
}
?>

<style>
    html, body { height: 100vh; overflow: hidden; background-color: #f4f7f6; }
    .main-wrapper { display: flex; height: 100vh; width: 100vw; }
    .content-area { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; }
    .sticky-header { background: white; border-bottom: 2px solid #eee; padding: 15px 30px; margin-left: 230px; flex-shrink: 0; }
    .fixed-search-bar { background: white; border-bottom: 1px solid #e5e5e5; padding: 10px 30px; margin-left: 230px; }
    .main-content { flex-grow: 1; overflow-y: auto; padding: 0 30px 100px 30px; position: relative; }

    #issuedTable { table-layout: fixed; width: 100%; border-collapse: separate; }
    #issuedTable th:nth-child(1) { width: 80px; }
    #issuedTable th:nth-child(2) { width: 25%; }
    #issuedTable th:nth-child(3) { width: 18%; }
    #issuedTable th:nth-child(4) { width: 110px; }
    #issuedTable th:nth-child(5) { width: 85px; }
    #issuedTable th:nth-child(6) { width: 85px; }
    #issuedTable th:nth-child(7) { width: 75px; }
    #issuedTable th:nth-child(8) { width: 130px; }

    #issuedTable td { vertical-align: middle; white-space: nowrap; position: relative; padding: 12px 5px; }
    
    /* CLICKABLE POP-UP STYLE */
    .expand-btn { display: block; overflow: hidden; text-overflow: ellipsis; cursor: pointer; color: inherit; text-decoration: none; }
    .expand-btn:hover { color: #007bff; text-decoration: underline; }

    .pop-info { 
        display: none; position: absolute; background: white; z-index: 1005; 
        white-space: normal; padding: 12px; border-radius: 6px; 
        box-shadow: 0 8px 24px rgba(0,0,0,0.2); border: 2px solid #141D49; 
        min-width: 280px; top: 50%; transform: translateY(-50%); left: 10px; 
    }

    #issuedTable thead th { position: sticky; top: 0; background: #f8f9fa!important; z-index: 10; box-shadow: inset 0 -1px 0 #dee2e6; font-size: 0.75rem; }
    .overdue-row { background-color: #fff5f5 !important; border-left: 4px solid #dc3545; }
    .due-soon-row { background-color: #fffbeb !important; border-left: 4px solid #f6e05e; }
    .nowrap { white-space: nowrap; }
</style>

<div class="main-wrapper">
    <?php include "../sidebar.php"; ?>
    <div class="content-area">
        <div class="sticky-header d-flex justify-content-between align-items-center">
            <h3 class="m-0 font-weight-bold"><i class="fas fa-book-reader text-success mr-2"></i> Issued Books</h3>
            <form method="POST">
                <button type="submit" name="bulk_mail" class="btn btn-danger btn-sm shadow-sm font-weight-bold">
                    <i class="fas fa-paper-plane mr-1"></i> REMIND OVERDUE
                </button>
            </form>
        </div>
        <div class="fixed-search-bar">
            <div class="row no-gutters align-items-center">
                <div class="col">
                    <input type="text" id="issuedSearch" class="form-control" placeholder="Quick Search Student, Book or PRN...">
                </div>
                <div class="col-auto pl-2">
                    <select id="statusFilter" class="form-control" style="width: 180px;">
                        <option value="all">All Active Loans</option>
                        <option value="overdue">⚠️ Overdue Only</option>
                        <option value="due-soon">⌛ Due Soon</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="main-content">
            <?php if(isset($_SESSION['mail_msg'])): ?>
                <div class="alert alert-info mt-3 alert-dismissible fade show shadow-sm">
                    <i class="fas fa-info-circle mr-2"></i> <?php echo $_SESSION['mail_msg']; unset($_SESSION['mail_msg']); ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>
            <div class="card shadow-sm border-0 mt-3">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0" id="issuedTable">
                        <thead>
                            <tr class="text-uppercase text-muted">
                                <th class="pl-3">Acc No</th>
                                <th>Book Title</th>
                                <th>Student</th>
                                <th>PRN</th>
                                <th>Issue</th>
                                <th>Due</th>
                                <th>Fine</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT ib.*, b.title, u.name as student_name FROM issued_books ib JOIN books b ON ib.accession_number = b.accession_number JOIN users u ON ib.prn = u.prn WHERE ib.status = 1 ORDER BY ib.due_date ASC";
                            $result = mysqli_query($connection, $query);
                            while ($row = mysqli_fetch_assoc($result)) {
                                $today = new DateTime(); $due = new DateTime($row['due_date']);
                                $is_overdue = ($today > $due);
                                $is_due_soon = (!$is_overdue && $today->diff($due)->days <= 2);
                                $row_class = $is_overdue ? 'overdue-row' : ($is_due_soon ? 'due-soon-row' : '');
                                $fine = $is_overdue ? ($due->diff($today)->days * $fine_rate) : 0;
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td class="pl-4"><code><?php echo $row['accession_number']; ?></code></td>
                                <td>
                                    <span class="expand-btn"><?php echo htmlspecialchars($row['title']); ?></span>
                                    <div class="pop-info"><?php echo htmlspecialchars($row['title']); ?></div>
                                </td>
                                <td>
                                    <span class="expand-btn"><?php echo htmlspecialchars($row['student_name']); ?></span>
                                    <div class="pop-info"><?php echo htmlspecialchars($row['student_name']); ?></div>
                                </td>
                                <td><code><?php echo $row['prn']; ?></code></td>
                                <td class="small nowrap"><?php echo date('d M', strtotime($row['issue_date'])); ?></td>
                                <td class="small nowrap font-weight-bold <?php echo $is_overdue ? 'text-danger' : ''; ?>">
                                    <?php echo date('d M', strtotime($row['due_date'])); ?>
                                </td>
                                <td><span class="badge <?php echo $is_overdue ? 'badge-danger' : 'badge-light border'; ?>">₹<?php echo $fine; ?></span></td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center">
                                        <a href="view-user-details.php?prn=<?php echo $row['prn']; ?>" class="btn btn-sm btn-outline-primary mr-1" title="Profile"><i class="fas fa-user"></i></a>
                                        <a href="view-issued-books.php?send_mail=<?php echo $row['id']; ?>" class="btn btn-sm btn-info mr-1" title="Mail"><i class="fas fa-envelope"></i></a>
                                        <a href="return-book.php?auto_accession=<?php echo $row['accession_number']; ?>" class="btn btn-sm btn-success" title="Return"><i class="fas fa-undo"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php } ?>
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
    // 1. COMBINED SEARCH & STATUS FILTER
    function applyFilters() {
        var searchValue = $("#issuedSearch").val().toLowerCase();
        var statusValue = $("#statusFilter").val();

        $("#issuedTable tbody tr").each(function() {
            var row = $(this);
            var text = row.text().toLowerCase();
            
            // Check Search Text
            var matchesSearch = text.indexOf(searchValue) > -1;
            
            // Check Status Dropdown
            var matchesStatus = true;
            if (statusValue === "overdue") {
                matchesStatus = row.hasClass('overdue-row');
            } else if (statusValue === "due-soon") {
                matchesStatus = row.hasClass('due-soon-row');
            }

            // Show row only if it passes BOTH filters
            if (matchesSearch && matchesStatus) {
                row.show();
            } else {
                row.hide();
            }
        });
    }

    // Listen for typing in search
    $("#issuedSearch").on("keyup", applyFilters);
    
    // Listen for changing the dropdown
    $("#statusFilter").on("change", applyFilters);
    // 2. Click to Expand Logic
    $('.expand-btn').on('click', function(e) {
        e.stopPropagation(); // Prevent closing immediately
        // Hide any other open pop-ups first
        $('.pop-info').not($(this).next('.pop-info')).fadeOut(100);
        // Toggle this specific one
        $(this).next('.pop-info').fadeToggle(200);
    });

    // 3. Click anywhere outside to close pop-ups
    $(document).on('click', function() {
        $('.pop-info').fadeOut(100);
    });
});
</script>