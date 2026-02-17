<?php
require_once "../includes/config.php";

if (isset($_POST['query'])) {

    $query = trim($_POST['query']);
    $query = mysqli_real_escape_string($connection, $query);

    $sql = "SELECT 
                b.accession_number,
                COALESCE(bm.title, b.title) AS title,
                COALESCE(bm.author, b.author) AS author,
                COALESCE(bm.publisher, b.publisher) AS publisher
            FROM books b
            LEFT JOIN book_master bm ON b.book_id = bm.book_id
            WHERE b.status = 1 
            AND b.is_deleted = 0
            AND (
                b.accession_number LIKE '%$query%'
                OR bm.title LIKE '%$query%'
                OR bm.author LIKE '%$query%'
                OR b.title LIKE '%$query%'
                OR b.author LIKE '%$query%'
            )
            LIMIT 15";

    $result = mysqli_query($connection, $sql);

    if ($result && mysqli_num_rows($result) > 0) {

        while ($row = mysqli_fetch_assoc($result)) {
            echo "
            <div class='book-item d-flex justify-content-between align-items-center shadow-sm mb-2'>
                <div>
                    <span class='badge badge-primary mb-1'>
                        Acc No: {$row['accession_number']}
                    </span>
                    <h6 class='mb-0 font-weight-bold'>"
                        . htmlspecialchars($row['title']) .
                    "</h6>
                    <small class='text-muted'>
                        <i class='fas fa-user-edit mr-1'></i>"
                        . htmlspecialchars($row['author']) .
                        " | <i class='fas fa-print mr-1'></i>"
                        . htmlspecialchars($row['publisher']) .
                    "</small>
                </div>
                <button type='button'
                        class='btn btn-sm btn-outline-primary select-book-btn font-weight-bold px-3'
                        data-acc='{$row['accession_number']}'>
                    SELECT
                </button>
            </div>";
        }

    } else {
        echo "<div class='p-4 text-center text-muted'>
                <i class='fas fa-search mb-2 fa-2x'></i><br>
                No available copies found for '<b>"
                . htmlspecialchars($query) .
                "</b>'.
              </div>";
    }
}
?>