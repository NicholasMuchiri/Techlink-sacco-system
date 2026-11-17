
<?php
require_once "includes/app.php";

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!csrf_valid("logout_csrf", $_POST["csrf_token"] ?? "")) {
        $error = "Invalid request. Please refresh and try again.";
    } else {
        $stmt = $pdo->prepare(
            "SELECT password_hash FROM members WHERE id = ? LIMIT 1"
        );

        $stmt->execute([(int) $_SESSION["member_id"]]);
        $account = $stmt->fetch();

        if ($account && password_verify(
            $_POST["password"] ?? "",
            $account["password_hash"]
        )) {

            // Clear session information.
            $_SESSION = [];

            // Remove the browser session cookie.
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();

                setcookie(
                    session_name(),
                    "",
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            // Destroy the session.
            session_destroy();

            // Return to the public main page.
            header("Location: index.php?loggedout=1");
            exit;

        } else {
            $error = "Incorrect password. You are still signed in.";
        }
    }
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Sign Out | TechLink Wealth</title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>

<div class="gate">

    <!-- LEFT BRAND PANEL -->
    <section class="gate-brand">

        <a href="index.php" class="brand">
            <span class="brand-mark">TW</span>
            <span>
                <strong>TechLink Wealth</strong>
                <small>Savings &amp; Credit</small>
            </span>
        </a>

        <div>
            <h1>Secure access for members and staff.</h1>

            <p>
                Your financial information deserves protection.
                Confirm your identity before leaving your account.
            </p>

            <ul class="gate-points">
                <li>Secure member accounts</li>
                <li>Savings and deposit history</li>
                <li>Credit applications and decisions</li>
            </ul>
        </div>

        <p>Innovate. Invest. Grow.</p>

    </section>

    <!-- RIGHT SIGN OUT PANEL -->
    <section class="gate-panel">

        <div class="gate-card">

            <p class="gate-kicker">ACCOUNT SECURITY</p>

            <h2>Sign out</h2>

            <p>
                Enter your password to confirm that you want
                to sign out of TechLink Wealth.
            </p>

            <?php if ($error !== ""): ?>
                <div class="form-error">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            
<div class="gate gate-single">
    <section class="gate-panel">
        <div class="gate-card">
            <?php if ($error !== ""): ?>
                <div class="form-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="logout.php">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token("logout_csrf")) ?>"
                >

                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                    autofocus
                >

                <button type="submit" class="primary-button">
                    Confirm sign out
                </button>
            </form>

            <div class="gate-links">
                <a href="dashboard.php">Cancel</a>
                <a href="index.php">Back home</a>
            </div>

        </div>
    </section>
</div>


</body>
</html>
