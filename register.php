
<?php
require_once "includes/app.php";
require_once "includes/mailer.php";

if (is_logged_in()) {
    redirect_workspace();
}

$full_name = "";
$email = "";
$phone = "";
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (
        $full_name === "" ||
        $email === "" ||
        $phone === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {
        $error = "Please complete all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!preg_match('/^[0-9+\-\s]{10,20}$/', $phone)) {
        $error = "Please enter a valid phone number.";
    } elseif (strlen($password) < 8) {
        $error = "Your password must contain at least 8 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "The passwords do not match.";
    } else {
        try {
            $check = $pdo->prepare(
                "SELECT id FROM members WHERE email = ? LIMIT 1"
            );
            $check->execute([$email]);

            if ($check->fetch()) {
                $error = "An account with this email already exists.";
            } else {
                // Generate a secure verification token.
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash("sha256", $token);

                // Read the application URL from mail configuration.
                $mailConfig = require __DIR__ . "/config/mail.php";

                if (empty($mailConfig["app_url"])) {
                    throw new RuntimeException(
                        "Application URL is missing from mail configuration."
                    );
                }

                $verificationUrl =
                    rtrim($mailConfig["app_url"], "/") .
                    "/verify_email.php?token=" .
                    urlencode($token);

                $pdo->beginTransaction();

                // Create the member account.
                $insert = $pdo->prepare(
                    "INSERT INTO members
                    (full_name, email, phone, password_hash)
                    VALUES (?, ?, ?, ?)"
                );

                $insert->execute([
                    $full_name,
                    $email,
                    $phone,
                    password_hash($password, PASSWORD_DEFAULT)
                ]);

                $memberId = (int) $pdo->lastInsertId();

                // Store only the hashed verification token.
                $saveToken = $pdo->prepare(
                    "INSERT INTO email_verification_tokens
                    (member_id, token_hash, expires_at)
                    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))"
                );

                $saveToken->execute([$memberId, $tokenHash]);

                // Send the actual verification email.
                $emailSent = sendVerificationEmail(
                    $email,
                    $full_name,
                    $verificationUrl
                );

                if (!$emailSent) {
                    throw new RuntimeException(
                        "Verification email could not be sent."
                    );
                }

                // Commit only after successful email delivery.
                $pdo->commit();

                $success =
                    "Registration successful! We have sent a verification link to your email address. Please check your inbox and spam folder. The link expires after 30 minutes.";

                $full_name = "";
                $email = "";
                $phone = "";
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log("Member registration failed: " . $e->getMessage());

            $error =
                "We could not complete your registration or send the verification email. Please check your details and try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Open an account | TechLink Wealth</title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>

    <div class="gate">

        <aside class="gate-brand">

            <a class="brand" href="index.php">
                <span class="mark">TW</span>
                <span>
                    <strong>TechLink Wealth</strong>
                    <small>Savings &amp; Credit</small>
                </span>
            </a>

            <div>
                <h1>Join the society.</h1>

                <p>
                    Create your membership account and verify
                    your email address to get started.
                </p>

                <ul class="gate-points">
                    <li>Your name, phone, and email</li>
                    <li>A password of at least 8 characters</li>
                    <li>Email verification required</li>
                </ul>
            </div>

            <p>Innovate. Invest. Grow.</p>

        </aside>

        <main class="gate-panel">

            <div class="gate-card">

                <p class="kicker">Membership</p>

                <h2>Open an account</h2>

                <p class="quiet">
                    This form is for members. Staff accounts
                    are issued by the society.
                </p>

                <?php if ($error !== ""): ?>
                    <div class="alert alert-error">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success !== ""): ?>

                    <div class="alert alert-success">
                        <?= e($success) ?>
                    </div>

                    <p>
                        <a href="login.php">Go to sign in</a>
                    </p>

                <?php else: ?>

                    <form method="POST" action="register.php">

                        <div class="field">
                            <label for="full_name">Full name</label>
                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                autocomplete="name"
                                required
                                value="<?= e($full_name) ?>"
                            >
                        </div>

                        <div class="field">
                            <label for="email">Email address</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                autocomplete="email"
                                required
                                value="<?= e($email) ?>"
                            >
                        </div>

                        <div class="field">
                            <label for="phone">Phone number</label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                autocomplete="tel"
                                required
                                value="<?= e($phone) ?>"
                            >
                        </div>

                        <div class="field">
                            <label for="password">Password</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >
                        </div>

                        <div class="field">
                            <label for="confirm_password">
                                Confirm password
                            </label>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >
                        </div>

                        <button
                            class="btn btn-green btn-block"
                            type="submit"
                        >
                            Register
                        </button>

                    </form>

                <?php endif; ?>

                <div class="form-links">
                    <a href="login.php">
                        Already a member? Sign in
                    </a>

                    <a href="index.php">Back home</a>
                </div>

            </div>

        </main>

    </div>

</body>
</html>
