
<?php

require_once "includes/app.php";

if (is_logged_in()) {
    redirect_workspace();
}

$error = "";
$success = "";

if (isset($_GET["verified"]) && $_GET["verified"] === "1") {
    $success = "Your email has been verified successfully. You can now sign in.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "Enter your email address and password.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        try {

            $stmt = $pdo->prepare(
                "SELECT
                    id,
                    full_name,
                    email,
                    password_hash,
                    role,
                    membership_status,
                    email_verified_at,
                    phone_verified_at,
                    membership_number
                 FROM members
                 WHERE email = ?
                 LIMIT 1"
            );

            $stmt->execute([$email]);

            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check email and password.
            if (
                !$account ||
                !password_verify($password, $account["password_hash"])
            ) {

                $error = "The email or password is incorrect.";

            } else {

                $status = strtolower($account["membership_status"]);

                // Block restricted accounts.
                if (in_array($status, ["suspended", "banned", "rejected"], true)) {

                    $error = "Your account is currently restricted. Please contact TechLink support.";

                // Require email verification.
                } elseif (empty($account["email_verified_at"])) {

    $error = "Please verify your email address before signing in. Check your inbox and spam folder.";

} elseif (!in_array($status, ["active", "approved"], true)) {

                    $error = "Your account is not active. Please contact TechLink support.";

                } else {

                    // Ensure verified accounts have a Membership ID.
                    if (empty($account["membership_number"])) {

                        $year = date("Y");

                        $membershipNumber = "TLW-" . $year . "-" .
                            str_pad(
                                (string) $account["id"],
                                6,
                                "0",
                                STR_PAD_LEFT
                            );

                        $update = $pdo->prepare(
                            "UPDATE members
                             SET membership_number = ?
                             WHERE id = ?
                             AND membership_number IS NULL"
                        );

                        $update->execute([
                            $membershipNumber,
                            $account["id"]
                        ]);
                    }

                    // Regenerate session ID.
                    session_regenerate_id(true);

                    $_SESSION["member_id"] = (int) $account["id"];
                    $_SESSION["member_name"] = $account["full_name"];
                    $_SESSION["role"] = $account["role"];
                    $_SESSION["membership_status"] = $status;

                    redirect_workspace();
                }
            }

        } catch (PDOException $e) {

            error_log("Sign-in failed: " . $e->getMessage());

            $error = "Sign-in is unavailable right now. Please try again.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sign in | TechLink Wealth</title>

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

                <h1>One entrance for members and staff.</h1>

                <p>
                    Sign in with the email on your account.
                    Members open their statement.
                    Administrators open the society books.
                </p>

                <ul class="gate-points">
                    <li>Savings and deposit history</li>
                    <li>Credit applications and decisions</li>
                    <li>Secure membership access</li>
                </ul>

            </div>

            <p>Innovate. Invest. Grow.</p>

        </aside>

        <main class="gate-panel">

            <div class="gate-card">

                <p class="kicker">Account access</p>

                <h2>Sign in</h2>

                <p class="quiet">
                    Use your registered email address and password.
                </p>

                <?php if ($success !== ""): ?>
                    <div class="alert alert-success">
                        <?= e($success) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error !== ""): ?>
                    <div class="alert alert-error">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">

                    <div class="field">

                        <label for="email">Email address</label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            autocomplete="username"
                            required
                            value="<?= e($_POST["email"] ?? "") ?>"
                        >

                    </div>

                    <div class="field">

                        <label for="password">Password</label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            required
                        >

                    </div>

                    <button
                        class="btn btn-green btn-block"
                        type="submit"
                    >
                        Sign in
                    </button>

                </form>

                <div class="form-links">

                    <a href="register.php">Open a member account</a>

                    <a href="index.php">Back home</a>

                </div>

            </div>

        </main>

    </div>

</body>

</html>
