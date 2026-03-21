<?php
require_once "../includes/config.php";

if(isset($_POST['id'])) {
    $id = mysqli_real_escape_string($connection, $_POST['id']);
    
    // 1. Fetch current fine settings from the database
    $settings = [];
    $set_res = mysqli_query($connection, "SELECT * FROM settings");
    while ($row = mysqli_fetch_assoc($set_res)) {
        $settings[$row['config_key']] = $row['config_value'];
    }

    $rate_low = $settings['fine_rate_low'] ?? 2;  // Default to 2 if not set
    $rate_high = $settings['fine_rate_high'] ?? 5; // Default to 5 if not set

    // 2. Fetch issue, book, and user details
    $query = "SELECT ib.*, b.title, u.name 
              FROM issued_books ib 
              JOIN books b ON ib.accession_number = b.accession_number 
              JOIN users u ON ib.prn = u.prn 
              WHERE ib.id = '$id'";
              
    $res = mysqli_fetch_assoc(mysqli_query($connection, $query));

    // 3. Tiered Fine Calculation Logic
    $due_date = new DateTime($res['due_date']);
    $today = new DateTime(date('Y-m-d'));
    $fine = 0;
    
    if($today > $due_date) {
        // Get total number of overdue days
        $days = (int)$today->diff($due_date)->format("%a");

        if($days <= 15) {
            // Rate for first 15 days
            $fine = $days * $rate_low;
        } else {
            // First 15 days at low rate + remaining days at high rate
            $fine = (15 * $rate_low) + (($days - 15) * $rate_high);
        }
    }

    echo "
    <div class='row'>
        <div class='col-md-7'>
            <h5 class='font-weight-bold text-primary'>{$res['title']}</h5>
            <p class='mb-1 text-muted'>Student: <b>{$res['name']}</b> ({$res['prn']})</p>
            <p class='mb-1 small'>Issued on: " . date('d M, Y', strtotime($res['issue_date'])) . "</p>
            <p class='text-danger font-weight-bold mb-0'>Due on: " . date('d M, Y', strtotime($res['due_date'])) . "</p>
            <small class='text-muted'>Total Overdue Days: <b>" . ($today > $due_date ? $days : 0) . "</b></small>
        </div>
        <div class='col-md-5 text-right'>
            <label class='font-weight-bold text-uppercase small text-danger'>Total Fine</label>
            <div class='input-group input-group-lg'>
                <div class='input-group-prepend'><span class='input-group-text bg-white'>₹</span></div>
                <input type='number' id='fine_input' class='form-control font-weight-bold text-danger' value='$fine'>
            </div>
            <small class='text-muted d-block mt-1'>₹$rate_low/day (1-15) | ₹$rate_high/day (>15)</small>
        </div>
    </div>
    <hr class='my-4'>
    <button class='btn btn-success btn-block font-weight-bold py-3 shadow-sm' id='confirm-return-btn' data-id='{$id}'>
        <i class='fas fa-check-circle mr-2'></i> CONFIRM RETURN & UPDATE INVENTORY
    </button>";
}
?>