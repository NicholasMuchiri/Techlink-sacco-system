<?php
require_once "includes/app.php";

$member = require_member(false);
$memberId = (int) $member["id"];
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if (!csrf_valid("profile_csrf", $_POST["csrf_token"] ?? "")) {
        $error = "This action expired. Refresh the page and try again.";
    } elseif ($action === "phone") {
        $phone = trim($_POST["phone"] ?? "");
        if (!preg_match('/^[0-9+\-\s]{10,20}$/', $phone)) {
            $error = "Please enter a valid phone number.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE members SET phone = ? WHERE id = ?");
                $stmt->execute([$phone, $memberId]);
                $_SESSION["profile_csrf"] = bin2hex(random_bytes(32));
                header("Location: my_profile.php?saved=phone");
                exit;
            } catch (PDOException $e) {
                error_log("Phone update failed: " . $e->getMessage());
                $error = "Unable to update your phone number.";
            }
        }
    } elseif ($action === "password") {
        $current = $_POST["current_password"] ?? "";
        $new = $_POST["new_password"] ?? "";
        $confirm = $_POST["confirm_password"] ?? "";

        if ($current === "" || $new === "" || $confirm === "") {
            $error = "Please complete all password fields.";
        } elseif (strlen($new) < 8) {
            $error = "Your new password must contain at least 8 characters.";
        } elseif ($new !== $confirm) {
            $error = "The new passwords do not match.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT password_hash FROM members WHERE id = ? LIMIT 1");
                $stmt->execute([$memberId]);
                $row = $stmt->fetch();

                if (!$row || !password_verify($current, $row["password_hash"])) {
                    $error = "Your current password is incorrect.";
                } else {
                    $upd = $pdo->prepare("UPDATE members SET password_hash = ? WHERE id = ?");
                    $upd->execute([password_hash($new, PASSWORD_DEFAULT), $memberId]);
                    session_regenerate_id(true);
                    $_SESSION["profile_csrf"] = bin2hex(random_bytes(32));
                    header("Location: my_profile.php?saved=password");
                    exit;
                }
            } catch (PDOException $e) {
                error_log("Password change failed: " . $e->getMessage());
                $error = "Unable to change your password.";
            }
        }
    } else {
        $error = "That profile action is not valid.";
    }
}

if (isset($_GET["saved"])) {
    $success = $_GET["saved"] === "password" ? "Password changed." : "Phone number updated.";
}
$status = strtolower((string) $member["membership_status"]);
$joined = strtotime((string) $member["created_at"]);

$token = csrf_token("profile_csrf");
portal_open("Profile", "profile");
?>

<section class="panel">
    <p class="kicker">Membership</p>
    <h2><?= e($member["full_name"]) ?></h2>
    <p class="quiet">The details held on the society register.</p>

    <dl class="profile-grid">
        <div>
            <dt>Full name</dt>
            <dd><?= e($member["full_name"]) ?></dd>
        </div>
        <div>
            <dt>Email</dt>
            <dd><?= e($member["email"]) ?></dd>
        </div>
        <div>
            <dt>Phone</dt>
            <dd><?= e($member["phone"]) ?></dd>
        </div>
        <div>
            <dt>Status</dt>
            <dd><span class="pill <?= e($status) ?>"><?= e($status) ?></span></dd>
        </div>
        <div>
            <dt>Registered</dt>
            <dd><?= $joined ? e(date("d M Y", $joined)) : "—" ?></dd>
        </div>
    </dl>
</section>

<section class="panel">
    <h2>Update details</h2>
    <?php if ($error !== ""): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success !== ""): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="my_profile.php">
        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
        <input type="hidden" name="action" value="phone">
        <div class="field">
            <label for="phone">Phone number</label>
            <input type="tel" id="phone" name="phone" autocomplete="tel" required value="<?= e($member["phone"]) ?>">
        </div>
        <button class="btn btn-green" type="submit">Save phone number</button>
    </form>
</section>

<section class="panel">
    <h2>Change password</h2>
    <form method="POST" action="my_profile.php">
        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
        <input type="hidden" name="action" value="password">
        <div class="field">
            <label for="current_password">Current password</label>
            <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
        </div>
        <div class="field">
            <label for="new_password">New password</label>
            <input type="password" id="new_password" name="new_password" minlength="8" autocomplete="new-password" required>
        </div>
        <div class="field">
            <label for="confirm_password">Confirm new password</label>
            <input type="password" id="confirm_password" name="confirm_password" minlength="8" autocomplete="new-password" required>
        </div>
        <button class="btn btn-green" type="submit">Change password</button>
    </form>
</section>

<?php portal_close(); ?>
