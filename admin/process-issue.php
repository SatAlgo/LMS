<?php
session_start();
// Prevent PHP from sending raw errors to the browser which breaks JSON
error_reporting(0); 
require_once "../includes/config.php";
require_once "../includes/mail-helper.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prn'])) {
    $prn = mysqli_real_escape_string($connection, $_POST['prn']);
    $acc_no = mysqli_real_escape_string($connection, $_POST['accession_number']);
    $due_date = mysqli_real_escape_string($connection, $_POST['due_date']);
    $issue_date = date('Y-m-d');

    mysqli_begin_transaction($connection);

    try {
        // 1. Verify Book Exists and is Available
        $book_q = mysqli_query($connection, "SELECT status, title FROM books WHERE accession_number='$acc_no' FOR UPDATE");
        $book = mysqli_fetch_assoc($book_q);

        if (!$book) throw new Exception("Book Acc No. $acc_no not found.");
        if ((int)$book['status'] === 0) throw new Exception("Book is already issued.");

        // 2. Verify Student Exists
        $student_q = mysqli_query($connection, "SELECT name, email FROM users WHERE prn='$prn'");
        $student = mysqli_fetch_assoc($student_q);
        if (!$student) throw new Exception("Student PRN $prn not found.");

        // 3. Update Database
        $insert = mysqli_query($connection, "INSERT INTO issued_books (prn, accession_number, issue_date, due_date, status) 
          VALUES ('$prn', '$acc_no', NOW(), '$due_date', 1)");
        $update = mysqli_query($connection, "UPDATE books SET status=0 WHERE accession_number='$acc_no'");

        if ($insert && $update) {
            mysqli_commit($connection);
            
            // --- 4. CONDITIONAL EMAIL TOGGLE ---
            // We check the 'mail_book_issuance' key from our settings
            if (isEmailEnabled($connection, 'mail_book_issuance')) {
                $body = "Hello {$student['name']},\n\nThe book '{$book['title']}' has been issued to your account.\nDue Date: $due_date.\n\nPlease return it on time to avoid fines.";
                sendLMSMail($student['email'], "MIT AOE Library - Book Issued", $body);
            }

            echo json_encode(['status' => 'success', 'message' => 'Book issued successfully']);
        } else {
            throw new Exception("Database update failed: " . mysqli_error($connection));
        }

    } catch (Exception $e) {
        mysqli_rollback($connection);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>