<?php
require_once "includes/app.php";

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

    if ($full_name === "" || $email === "" || $phone === "" || $password === "" || $confirm_password === "") {
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
            $check = $pdo->prepare("SELECT id FROM members WHERE email = ? LIMIT 1");
            $check->execute([$email]);

            if ($check->fetch()) {
                $error = "An account with this email already exists.";
            } else {
                $insert = $pdo->prepare(
                    "INSERT INTO members (full_name, email, phone, password_hash)
                     VALUES (?, ?, ?, ?)"
                );
                $pdo->beginTransaction();
                $insert->execute([
                    $full_name,
                    $email,
                    $phone,
                    password_hash($password, PASSWORD_DEFAULT),
                ]);
                $newId = (int) $pdo->lastInsertId();
                $account = "TW" . str_pad((string) $newId, 6, "0", STR_PAD_LEFT);
                $pdo->prepare("UPDATE members SET account_number = ? WHERE id = ?")->execute([$account, $newId]);
                $pdo->commit();

                $success = "Your membership application is in. Your account number is " . $account . ". You can sign in while it is reviewed.";
                $full_name = "";
                $email = "";
                $phone = "";
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
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
    <title>Open an account | TechLink Wealth</title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <div class="gate" style="background: linear-gradient(rgba(255,255,255,0.8), rgba(255,255,255,0.8)), url('images/hero.jpg') center / cover no-repeat fixed;">
   
        <main class="gate-panel">
            <div class="gate-card">
                <p class="kicker">Membership</p>
                <h2>Open an account</h2>
               
                <?php if ($error !== ""): ?>
                    <div class="alert alert-error"><?= e($error) ?></div>
                <?php endif; ?>

                <?php if ($success !== ""): ?>
                    <div class="alert alert-success">
                        <?= e($success) ?>
                        <p><a href="login.php">Sign in</a></p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php">
                    <div class="field">
                        <label for="full_name">Full name</label>
                        <input type="text" id="full_name" name="full_name" autocomplete="name" required value="<?= e($full_name) ?>">
                    </div>
                    <div class="field">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email" autocomplete="email" required value="<?= e($email) ?>">
                    </div>
                    <div class="field">
                        <label for="phone">Phone number</label>
                        <input type="tel" id="phone" name="phone" autocomplete="tel" required value="<?= e($phone) ?>">
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required>
                    </div>
                    <div class="field">
                        <label for="confirm_password">Confirm password</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="8" autocomplete="new-password" required>
                    </div>
                    <button class="btn btn-green btn-block" type="submit">Submit application</button>
                </form>

                <div class="form-links">
                    <a href="login.php">Already a member? Sign in</a>
                    <a href="index.php">Back home</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>