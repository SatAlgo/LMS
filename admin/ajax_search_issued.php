<?php
require_once "../includes/config.php";
if(isset($_POST['query'])) {
    $q = mysqli_real_escape_string($connection, $_POST['query']);
    $query = "SELECT ib.id, ib.accession_number, b.title, u.name 
              FROM issued_books ib
              JOIN books b ON ib.accession_number = b.accession_number
              JOIN users u ON ib.prn = u.prn
              WHERE (ib.accession_number = '$q' OR ib.prn = '$q' OR u.name LIKE '%$q%') 
              AND ib.status = 1"; // Only active issues
    
    $result = mysqli_query($connection, $query);
    if(mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            echo "
            <div class='p-2 border-bottom d-flex justify-content-between align-items-center'>
                <small><b>{$row['accession_number']}</b> - {$row['title']}</small>
                <button class='btn btn-sm btn-outline-success select-issue-btn' data-id='{$row['id']}'>Select</button>
            </div>";
        }
    } else {
        echo "<div class='p-3 text-center text-muted'>No active issues found.</div>";
    }
}
?>