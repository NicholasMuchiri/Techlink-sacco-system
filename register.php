
<?php
session_start();

require_once "db.php";

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
                $password_hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $insert = $pdo->prepare(
                    "INSERT INTO members
                    (full_name, email, phone, password_hash)
                    VALUES (?, ?, ?, ?)"
                );

                $insert->execute([
                    $full_name,
                    $email,
                    $phone,
                    $password_hash
                ]);

                $success = "Registration successful! You can now log in.";

                $full_name = "";
                $email = "";
                $phone = "";
            }
        } catch (PDOException $e) {
            error_log("Member registration failed: " . $e->getMessage());
            $error = "We could not complete your registration. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Registration | TechLink Wealth</title>
    <link rel="stylesheet" href="register.css">
</head>
<body class="register-page">

    <header class="register-brand">
        <div class="brand-mark">TW</div>
        <h1>TechLink Wealth</h1>
        <p>Innovate. Invest. Grow.</p>
    </header>

    <main class="register-card">
        <h2>Create your account</h2>
        <p class="register-description">
            Enter your details to register as a member.
        </p>

        <?php if ($error !== ""): ?>
            <div class="message error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <div class="message success">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                <p><a href="login.php">Go to Member Login</a></p>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="full_name">Full name</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="<?= htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="name"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="form-group">
                <label for="phone">Phone number</label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="tel"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Create password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button type="submit" class="register-button">
                Create Account
            </button>
        </form>

        <p class="login-prompt">
            Already have an account?
            <a href="login.php">Sign in</a>
        </p>
    </main>

    <div class="back-home">
        <a href="index.php">&larr; Back to homepage</a>
    </div>

    <footer class="register-footer">
        &copy; <?= date("Y") ?> TechLink Wealth Investments Limited
    </footer>

</body>
</html>
