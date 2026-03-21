<?php
require_once "../includes/config.php";
require_once "../includes/functions.php"; // Ensure your new function file is included

if(isset($_POST['id'])) {
    $id = mysqli_real_escape_string($connection, $_POST['id']);
    
    // 1. Fetch current settings for the UI labels (Standard/Late rates and Period)
    $settings = [];
    $set_res = mysqli_query($connection, "SELECT * FROM settings");
    while ($row = mysqli_fetch_assoc($set_res)) {
        $settings[$row['config_key']] = $row['config_value'];
    }

    // Dynamic Variables from Settings
    $rate_low = $settings['fine_rate'] ?? 2;
    $rate_high = $settings['fine_rate_late'] ?? 5;
    $loan_period = $settings['default_loan_period'] ?? 7;

    // 2. Fetch issue, book, and user details
    $query = "SELECT ib.*, b.title, u.name 
              FROM issued_books ib 
              JOIN books b ON ib.accession_number = b.accession_number 
              JOIN users u ON ib.prn = u.prn 
              WHERE ib.id = '$id'";
              
    $res = mysqli_fetch_assoc(mysqli_query($connection, $query));

    // 3. IMPROVED FINE CALCULATION (Historical & Dynamic)
    $due_date = $res['due_date'];
    $today = date('Y-m-d');
    
    // Call the new fair function that handles the 10x + 5y logic automatically
    $fine = getFairFine($due_date, $today, $connection);
    
    // Calculate total overdue days for display
    $due_dt_obj = new DateTime($due_date);
    $today_obj = new DateTime($today);
    $days = ($today_obj > $due_dt_obj) ? (int)$today_obj->diff($due_dt_obj)->format("%a") : 0;

    echo "
    <div class='row'>
        <div class='col-md-7'>
            <h5 class='font-weight-bold text-primary'>{$res['title']}</h5>
            <p class='mb-1 text-muted'>Student: <b>{$res['name']}</b> ({$res['prn']})</p>
            <p class='mb-1 small'>Issued on: " . date('d M, Y', strtotime($res['issue_date'])) . "</p>
            <p class='text-danger font-weight-bold mb-0'>Due on: " . date('d M, Y', strtotime($due_date)) . "</p>
            <small class='text-muted'>Total Overdue Days: <b>$days</b></small>
        </div>
        <div class='col-md-5 text-right'>
            <label class='font-weight-bold text-uppercase small text-danger'>Total Fine</label>
            <div class='input-group input-group-lg'>
                <div class='input-group-prepend'><span class='input-group-text bg-white'>₹</span></div>
                <input type='number' id='fine_input' class='form-control font-weight-bold text-danger' value='$fine'>
            </div>
            <small class='text-muted d-block mt-1'>
                ₹$rate_low/day (1-$loan_period) | ₹$rate_high/day (>$loan_period)
            </small>
        </div>
    </div>
    <hr class='my-4'>
    <button class='btn btn-success btn-block font-weight-bold py-3 shadow-sm' id='confirm-return-btn' data-id='{$id}'>
        <i class='fas fa-check-circle mr-2'></i> CONFIRM RETURN & UPDATE INVENTORY
    </button>";
}
?>