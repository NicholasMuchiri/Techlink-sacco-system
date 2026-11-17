
<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

function sendVerificationEmail($toEmail, $toName, $verificationUrl)
{
    $config = require __DIR__ . '/../config/mail.php';

    try {
        $mail = new PHPMailer(true);

        // SMTP configuration
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) $config['port'];

        // Sender details
        $mail->setFrom(
            $config['from_email'],
            $config['from_name']
        );

        // Recipient
        $mail->addAddress($toEmail, $toName);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Verify your TechLink account';

        $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $safeUrl  = htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8');

        $mail->Body = "
            <h2>Welcome to TechLink Wealth Investments Limited</h2>
            <p>Hello {$safeName},</p>
            <p>Thank you for registering with TechLink.</p>
            <p>Please click the button below to verify your email address:</p>
            <p>
                <a href=\"{$safeUrl}\"
                   style=\"background:#14532d;color:white;padding:12px 20px;
                   text-decoration:none;border-radius:6px;display:inline-block;\">
                   Verify My Email Address
                </a>
            </p>
            <p>This verification link expires after 30 minutes.</p>
            <p>Innovate. Invest. Grow.</p>
        ";

        $mail->AltBody =
            "Hello {$toName},\n\n" .
            "Please verify your TechLink email address using this link:\n" .
            $verificationUrl .
            "\n\nThis link expires after 30 minutes.";

        $mail->send();

        return true;

    } catch (\Throwable $e) {
        error_log('TechLink email error: ' . $e->getMessage());

        if (isset($mail)) {
            error_log('PHPMailer details: ' . $mail->ErrorInfo);
        }

        return false;
    }
}
