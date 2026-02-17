<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header("Location: ../index.php");
    exit();
}
?>

<style>
    /* Unified Layout - Kept exactly as your current code */
    html, body { height: 100vh; overflow: hidden; background-color: #f4f7f6; }
    .main-wrapper { display: flex; height: 100vh; width: 100vw; }
    .content-area { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; margin-left: 10px; }
    .sticky-header { background: #ffffff; border-bottom: 1px solid #dee2e6; padding: 20px 30px; margin-left: 230px; flex-shrink: 0; }
    .main-content { padding: 10px 25px 100px 25px; flex-grow: 1; overflow-y: hidden; }

    #details-container { display: none; } /* Stage 2 hidden by default */
</style>

<div class="main-wrapper">
    <?php include "../sidebar.php"; ?>

    <div class="content-area">
        <div class="sticky-header">
            <h3 class="m-0 font-weight-bold"><i class="fas fa-undo text-success mr-2"></i> Process Book Return</h3>
        </div>

        <div class="main-content">
            <div class="row">
                <div class="col-md-5">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <label class="font-weight-bold small text-uppercase text-muted">Step 1: Find Active Issue</label>
                            <div class="input-group mb-4">
                                <input type="text" id="search_query" class="form-control" placeholder="Enter PRN or Accession No.">
                                <div class="input-group-append">
                                    <button class="btn btn-success" id="btn-search">
                                        <i class="fas fa-search mr-2"></i>Search
                                    </button>
                                </div>
                            </div>
                            <div id="results-container" style="max-height: 400px; overflow-y: auto;">
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-search fa-2x mb-2"></i>
                                    <p class="small">Enter details to see active issues.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-7" id="details-container">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white font-weight-bold">Step 2: Verify Fine & Confirm</div>
                        <div class="card-body" id="book-details-content">
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
    // 1. STAGE 1: Search Logic (Fixed button disappearing)
    $('#btn-search').click(function() {
        let query = $('#search_query').val();
        let btn = $(this);
        if(query.length > 0) {
            // Updated to keep text and icon visible while loading
            btn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Searching').prop('disabled', true);
            
            $.ajax({
                url: 'ajax_search_issued.php',
                method: 'POST',
                data: { query: query },
                success: (data) => {
                    $('#results-container').html(data);
                    // Revert button to original state
                    btn.html('<i class="fas fa-search mr-2"></i>Search').prop('disabled', false);
                },
                error: function() {
                    btn.html('<i class="fas fa-search mr-2"></i>Search').prop('disabled', false);
                }
            });
        }
    });

    // 2. STAGE 2: Selection Logic (Fixed SELECT to SELECTED)
    $(document).on('click', '.select-issue-btn', function() {
        let btn = $(this);
        let issueId = btn.data('id');

        // UI Feedback: Reset other buttons and mark this one as SELECTED
        $('.select-issue-btn').text('Select').removeClass('btn-success').addClass('btn-outline-success').prop('disabled', false);
        btn.text('SELECTED').removeClass('btn-outline-success').addClass('btn-success').prop('disabled', true);

        $('#details-container').fadeIn();
        $('#book-details-content').html('<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>');
        
        $.ajax({
            url: 'get_issue_details.php',
            method: 'POST',
            data: { id: issueId },
            success: function(html) {
                $('#book-details-content').html(html);
            }
        });
    });

    // 3. STAGE 3: Final Transaction Sync
    $(document).on('click', '#confirm-return-btn', function() {
        let issueId = $(this).data('id');
        let finalFine = $('#fine_input').val();
        let btn = $(this);
        
        if(confirm('Confirm return for this book? This will update inventory and record the fine of ₹' + finalFine)) {
            btn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...').prop('disabled', true);
            
            $.ajax({
                url: 'process-return.php',
                method: 'POST',
                data: { id: issueId, fine: finalFine },
                dataType: 'json',
                success: function(resp) {
                    if(resp.status === 'success') {
                        // Professional UI Notification inside the card
                        $('#book-details-content').prepend(`
                            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                                <i class="fas fa-check-circle mr-2"></i> <strong>Success!</strong> Book has been returned successfully.
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                            </div>
                        `);
                        
                        // Disable the confirm button as it's already done
                        btn.hide(); 
                        
                        // Reload the page after 2 seconds to refresh the search list and dashboard
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        alert("Error: " + resp.message);
                        btn.html('<i class="fas fa-check-circle mr-2"></i> CONFIRM RETURN & UPDATE INVENTORY').prop('disabled', false);
                    }
                }
            });
        }
    });
});
</script>

<script>
$(document).ready(function() {
    // Check if the URL has the auto_accession parameter
    const urlParams = new URLSearchParams(window.location.search);
    const autoAcc = urlParams.get('auto_accession');

    if (autoAcc) {
        // 1. Fill the search input with the accession number
        $('#search_query').val(autoAcc);

        // 2. Automatically trigger the search button click
        $('#btn-search').trigger('click');
        
        // 3. Optional: Clear the URL parameter so refreshing doesn't re-trigger it
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
</script>