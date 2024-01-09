<?php
$emailRecipient = 'fernandohowells@gmail.com';
$subject = 'Mercury test mail';
$message = 'If you can read this, everything was fine!';
$headers = 'From: postmaster@localhost' . "\r\n" .
           'Reply-To: fernandohowells@gmail.com' . "\r\n" .
           'X-Mailer: PHP/' . phpversion();

// Set the Return-Path header
ini_set('postmaster@localhost', 'fernandohowells@gmail.com');

if (mail($emailRecipient, $subject, $message, $headers)) {
    echo 'Email sent successfully.';
} else {
    echo 'Email could not be sent.';
}
?>