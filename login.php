<?php
require_once "includes/app.php";

if (is_logged_in()) {
    redirect_workspace();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Enter your email address and password.";
    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT id, full_name, email, password_hash, role, membership_status
                 FROM members
                 WHERE email = ?
                 LIMIT 1"
            );
            $stmt->execute([$email]);
            $account = $stmt->fetch();

            $valid = $account && password_verify($password, $account["password_hash"]);

            if (!$valid) {
                $error = "The email or password is incorrect.";
            } else {
                session_regenerate_id(true);
                $_SESSION["member_id"] = (int) $account["id"];
                $_SESSION["member_name"] = $account["full_name"];
                $_SESSION["role"] = $account["role"];
                $_SESSION["membership_status"] = $account["membership_status"];
                redirect_workspace();
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
    <div class="gate" style="background: linear-gradient(rgba(255,255,255,0.8), rgba(255,255,255,0.8)), url('images/hero.jpg') center / cover no-repeat fixed;">
   
        <main class="gate-panel">
            <div class="gate-card">
                <p class="kicker">Account access</p>
                <h2>Sign in</h2>
               
                <?php if ($error !== ""): ?>
                    <div class="alert alert-error"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="field">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email" autocomplete="username" required value="<?= e($_POST["email"] ?? "") ?>">
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" autocomplete="current-password" required>
                    </div>
                    <button class="btn btn-green btn-block" type="submit">Sign in</button>
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
