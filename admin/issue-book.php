<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header("Location: ../index.php");
    exit();
}

// 1. Fetch Dynamic Loan Period from Settings (e.g., 14 days)
$loan_res = mysqli_query($connection, "SELECT config_value FROM settings WHERE config_key = 'default_loan_period'");
$loan_days = mysqli_fetch_assoc($loan_res)['config_value'] ?? 14;

// Calculate dates for the UI
$today = date('Y-m-d');
$default_due_date = date('Y-m-d', strtotime("+$loan_days days"));
?>

<style>
    /* Unified Layout Design: No Page Scrolling */
    html, body { height: 100vh; overflow: hidden; background-color: #f4f7f6; }
    .main-wrapper { display: flex; height: 100vh; width: 100vw; }
    .content-area { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; margin-left: 0; }
    .sticky-top-header { background: white; border-bottom: 2px solid #eee; padding: 20px 30px; margin-left: 240px; flex-shrink: 0; }
    .main-content { padding: 30px; flex-grow: 1; overflow-y: auto; margin-left: 240px; }

    #book-list-container { max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; background: white; display: none; z-index: 1050; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    .book-item { padding: 15px; border-bottom: 1px solid #f1f1f1; transition: 0.2s; }
    .selected-book-item { background: #e7f3ff !important; border: 2px solid #007bff !important; border-radius: 8px; }
</style>

<div class="main-wrapper">
    <?php include "../sidebar.php"; ?>

    <div class="content-area">
        <div class="sticky-top-header">
            <h3 class="m-0 font-weight-bold"><i class="fas fa-plus-circle text-primary mr-2"></i> Issue Book</h3>
        </div>

        <div class="main-content">
            <div class="row">
                <div class="col-md-7">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <label class="font-weight-bold">Search Available Books</label>
                            <div class="input-group mb-3">
                                <input type="text" id="book-search" class="form-control" placeholder="Type Title or Author...">
                                <div class="input-group-append">
                                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                </div>
                            </div>
                            <div id="book-list-container"></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <form id="issueForm">
                                <div class="form-group">
                                    <label class="font-weight-bold">Student PRN</label>
                                    <input type="text" name="prn" id="prn_input" class="form-control" placeholder="Enter PRN" required>
                                    <div id="prn_status" class="mt-1 small"></div>
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-bold">Accession Number</label>
                                    <input type="text" id="acc_no_field" name="accession_number" class="form-control bg-light" readonly required>
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-bold">Due Date</label>
                                    <input type="date" name="due_date" id="due_date" class="form-control" 
                                           value="<?php echo $default_due_date; ?>" 
                                           min="<?php echo $today; ?>" required>
                                    <small class="text-info">Default period: <?php echo $loan_days; ?> days</small>
                                </div>
                                <button type="submit" id="submitBtn" class="btn btn-primary btn-block font-weight-bold py-2 shadow-sm" disabled>
                                    ISSUE BOOK
                                </button>
                            </form>
                            <div id="responseMessage" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div> 
        </div> 
    </div> 
</div>

<?php include "../includes/footer.php"; ?>

<script>
$(document).ready(function() {
    let studentReady = false, bookReady = false;

    function checkReady() { 
        $('#submitBtn').prop('disabled', !(studentReady && bookReady)); 
    }

    // 1. Restriction: Prevent manually typing past dates if browser supports it
    $('#due_date').on('change', function() {
        let selectedDate = new Date($(this).val());
        let today = new Date();
        today.setHours(0,0,0,0);
        
        if (selectedDate < today) {
            alert("Due date cannot be in the past!");
            $(this).val('<?php echo $default_due_date; ?>');
        }
    });

    // 2. Book Search
    $('#book-search').on('input', function() {
        let q = $(this).val();
        if(q.length > 1) {
            $.ajax({ 
                url: 'ajax_search_books.php', 
                method: 'POST', 
                data: {query: q}, 
                success: (d) => { $('#book-list-container').html(d).show(); } 
            });
        } else { $('#book-list-container').hide(); }
    });

    // 3. Student Check
    $('#prn_input').on('input', function() {
        let prn = $(this).val();
        if(prn.length > 5) {
            $.ajax({ 
                url: 'check_user.php', 
                method: 'POST', 
                data: {prn: prn}, 
                success: (res) => {
                    $('#prn_status').html(res);
                    studentReady = res.includes('text-success');
                    checkReady();
                }
            });
        }
    });

    // 4. Selection
    $(document).on('click', '.select-book-btn', function() {
        let selectedAcc = $(this).data('acc');
        let selectedItem = $(this).closest('.book-item');
        $('#acc_no_field').val(selectedAcc);
        $('.book-item').not(selectedItem).remove();
        selectedItem.addClass('selected-book-item');
        $(this).text('SELECTED').removeClass('btn-outline-primary').addClass('btn-success').prop('disabled', true);
        bookReady = true;
        checkReady();
    });

    // 5. Submit Transaction
    $('#issueForm').on('submit', function(e) {
        e.preventDefault();
        $('#submitBtn').html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
        $.ajax({
            url: 'process-issue.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp) {
                if(resp.status === 'success') {
                    $('#responseMessage').html('<div class="alert alert-success">' + resp.message + '</div>');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    $('#responseMessage').html('<div class="alert alert-danger">' + resp.message + '</div>');
                    $('#submitBtn').text('ISSUE BOOK').prop('disabled', false);
                }
            }
        });
    });
});
</script>