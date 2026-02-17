<?php
session_start();
error_reporting(0); // Prevent errors from breaking JSON
require_once "../includes/config.php";
require_once "../includes/mail-helper.php"; // Ensure this is included!

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $issue_id = mysqli_real_escape_string($connection, $_POST['id']);
    $fine = mysqli_real_escape_string($connection, $_POST['fine']);
    $return_date = date('Y-m-d');

    mysqli_begin_transaction($connection);

    try {
        $info_q = "SELECT ib.accession_number, u.email, u.name, b.title 
                   FROM issued_books ib 
                   JOIN users u ON ib.prn = u.prn 
                   JOIN books b ON ib.accession_number = b.accession_number 
                   WHERE ib.id = '$issue_id' AND ib.status = 1";
        
        $info_res = mysqli_query($connection, $info_q);
        $data = mysqli_fetch_assoc($info_res);

        if (!$data) throw new Exception("Active issue record not found.");

        $acc_no = $data['accession_number'];
        $update_issue = "UPDATE issued_books SET return_date = '$return_date', fine = '$fine', status = 0 WHERE id = '$issue_id'";
        $update_book = "UPDATE books SET status = 1 WHERE accession_number = '$acc_no'";

        if (mysqli_query($connection, $update_issue) && mysqli_query($connection, $update_book)) {
            mysqli_commit($connection);

            // --- 4. CONSITIONAL EMAIL USING YOUR WORKING HELPER ---
            if (isEmailEnabled($connection, 'mail_return_reminders')) {
                $subject = "Book Returned Successfully - MIT AOE Library";
                
                // Create a clean text or HTML body depending on what sendLMSMail expects
                $body = "Hello {$data['name']},\n\n" .
                        "The book '{$data['title']}' has been returned successfully.\n" .
                        "Return Date: " . date('d M, Y') . "\n" .
                        "Fine Paid: ₹$fine\n\n" .
                        "Thank you for using the MIT AOE Library.";

                // USE YOUR WORKING HELPER HERE
                sendLMSMail($data['email'], $subject, $body);
            }

            echo json_encode(['status' => 'success', 'message' => 'Book returned successfully!']);
        } else {
            throw new Exception("Database update failed.");
        }
    } catch (Exception $e) {
        mysqli_rollback($connection);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>