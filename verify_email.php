
<?php

require_once "includes/app.php";

$message = "";
$status = "error";

$token = trim($_GET["token"] ?? "");

if ($token === "" || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $message = "Invalid verification link. Please check your email.";
} else {

    try {
        $tokenHash = hash("sha256", $token);

        $pdo->beginTransaction();

        // Find and lock the verification token and member.
        $stmt = $pdo->prepare(
            "SELECT
                t.id AS token_id,
                t.member_id,
                t.expires_at,
                t.used_at,
                m.full_name,
                m.email_verified_at,
                m.membership_number
             FROM email_verification_tokens t
             INNER JOIN members m ON m.id = t.member_id
             WHERE t.token_hash = ?
             LIMIT 1
             FOR UPDATE"
        );

        $stmt->execute([$tokenHash]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {

            $pdo->rollBack();
            $message = "This verification link is invalid or has already been used.";

        } elseif ($record["used_at"] !== null) {

            $pdo->rollBack();
            $message = "This verification link has already been used. Please sign in.";

            if ($record["email_verified_at"] !== null) {
                $status = "success";
            }

        } elseif (strtotime($record["expires_at"]) <= time()) {

            $pdo->rollBack();
            $message = "This verification link has expired. Please request a new one.";

        } else {

            $memberId = (int) $record["member_id"];

            // Generate the Membership ID after email verification.
            // Keep an existing Membership ID unchanged.
            $membershipNumber = $record["membership_number"];

            if (empty($membershipNumber)) {

                $year = date("Y");

                $membershipNumber = "TLW-" . $year . "-" .
                    str_pad((string) $memberId, 6, "0", STR_PAD_LEFT);
            }

            // Verify email, activate account and assign Membership ID.
            $updateMember = $pdo->prepare(
                "UPDATE members
                 SET
                    email_verified_at = COALESCE(email_verified_at, NOW()),
                    membership_number = ?,
                    membership_status = 'active'
                 WHERE id = ?"
            );

            $updateMember->execute([
                $membershipNumber,
                $memberId
            ]);

            // Mark this token as used.
            $updateToken = $pdo->prepare(
                "UPDATE email_verification_tokens
                 SET used_at = NOW()
                 WHERE id = ?"
            );

            $updateToken->execute([
                $record["token_id"]
            ]);

            // Invalidate other unused verification links.
            $otherTokens = $pdo->prepare(
                "UPDATE email_verification_tokens
                 SET used_at = NOW()
                 WHERE member_id = ?
                 AND used_at IS NULL"
            );

            $otherTokens->execute([
                $memberId
            ]);

            $pdo->commit();

            $status = "success";

            $message =
                "Your email has been successfully verified! " .
                "Your TechLink Membership ID is " .
                $membershipNumber .
                ". You can now sign in using your email and password.";
        }

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Email verification error: " . $e->getMessage());

        $message =
            "We could not complete your email verification. Please try again later.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Email Verification | TechLink Wealth</title>

    <link rel="stylesheet" href="css/app.css">
</head>

<body>

<div class="gate">

    <main class="gate-panel">

        <div class="gate-card">

            <p class="kicker">TechLink Wealth</p>

            <?php if ($status === "success"): ?>

                <h2>Email verified!</h2>

                <div class="alert alert-success">
                    <?= e($message) ?>
                </div>

                <a href="login.php" class="btn btn-green btn-block">
                    Sign In
                </a>

            <?php else: ?>

                <h2>Verification unsuccessful</h2>

                <div class="alert alert-error">
                    <?= e($message) ?>
                </div>

                <a href="login.php">
                    Return to Sign In
                </a>

            <?php endif; ?>

        </div>

    </main>

</div>

</body>
</html>
