<?php
// 1. Link the core PHPMailer files you just copied
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'includes/PHPMailer/Exception.php';
require 'includes/PHPMailer/PHPMailer.php';
require 'includes/PHPMailer/SMTP.php';

// 2. Initialize PHPMailer
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = 'smtp.gmail.com';                       // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = 'algorithmseven@gmail.com';                 // YOUR GMAIL ADDRESS
    $mail->Password   = 'rafj opvi ncmb hnru';                   // YOUR GMAIL APP PASSWORD
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption
    $mail->Port       = 587;                                    // TCP port to connect to

    // Recipients
    $mail->setFrom('your-email@gmail.com', 'LMS Test System');  // Use your email here
    $mail->addAddress('your-personal-email@gmail.com');         // WHERE TO SEND THE TEST

    // Content
    $mail->isHTML(true);                                        // Set email format to HTML
    $mail->Subject = 'LMS Mail System Test';
    $mail->Body    = '<h3>Success!</h3><p>Your Library Management System is now connected to the mail server.</p>';

    $mail->send();
    echo '<div style="color: green; font-weight: bold;">Message has been sent successfully!</div>';
} catch (Exception $e) {
    echo "<div style='color: red;'>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</div>";
}
?>