<?php
require_once "../includes/config.php";
if(isset($_POST['prn'])) {
    $prn = mysqli_real_escape_string($connection, $_POST['prn']);
    $user_res = mysqli_query($connection, "SELECT name FROM users WHERE prn = '$prn' LIMIT 1");
    if(mysqli_num_rows($user_res) > 0) {
        $user = mysqli_fetch_assoc($user_res);
        $limit_res = mysqli_query($connection, "SELECT config_value FROM settings WHERE config_key = 'issue_limit'");
        $max_limit = mysqli_fetch_assoc($limit_res)['config_value'] ?? 3;
        $current_count = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as count FROM issued_books WHERE prn = '$prn' AND status = 1"))['count'];
        if($current_count >= $max_limit) {
            echo "<span class='text-danger small'><i class='fas fa-times'></i> <b>{$user['name']}</b> has reached the limit ($current_count/$max_limit).</span>";
        } else {
            echo "<span class='text-success small'><i class='fas fa-check'></i> <b>{$user['name']}</b> (Issued: $current_count / Max: $max_limit)</span>";
        }
    } else { echo "<span class='text-danger small'>Invalid PRN: Not registered.</span>"; }
}
?>