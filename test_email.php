
<?php

require_once "includes/mailer.php";

$sent = sendVerificationEmail(
    "gichukidavid54@gmail.com",
    "TechLink Test",
    "http://localhost/Techlink-sacco-system/verify_email.php?token=test"
);

if ($sent) {
    echo "SUCCESS: Gmail sent the test email.";
} else {
    echo "FAILED: Check C:\\xampp\\apache\\logs\\error.log";
}
