<?php
require_once "../includes/config.php";

$q = mysqli_real_escape_string($connection, $_GET['q'] ?? '');

$res = mysqli_query($connection,"
    SELECT title, SUM(price) AS total
    FROM books
    WHERE title LIKE '%$q%' AND is_deleted=0
    GROUP BY title
    LIMIT 5");

if (mysqli_num_rows($res) == 0) {
    echo "No matching book found";
    exit;
}

while ($r = mysqli_fetch_assoc($res)) {
    echo "<div><strong>{$r['title']}</strong> — ₹".
         number_format($r['total'])."</div>";
}