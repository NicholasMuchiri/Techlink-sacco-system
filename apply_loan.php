<?php
require_once "includes/app.php";

$member = require_member(true);
$memberId = (int) $member["id"];
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $amount = trim($_POST["amount_requested"] ?? "");
    $purpose = trim($_POST["purpose"] ?? "");

    if (!csrf_valid("loan_csrf_token", $_POST["csrf_token"] ?? "")) {
        $error = "Your session verification failed. Refresh the page and try again.";
    } elseif (
        !preg_match('/^[0-9]{1,13}(\.[0-9]{1,2})?$/', $amount) ||
        (float) $amount <= 0 ||
        (float) $amount > 9999999999999.99
    ) {
        $error = "Enter a valid loan amount greater than zero.";
    } elseif ($purpose === "" || strlen($purpose) > 5000) {
        $error = "Enter the purpose of your loan (maximum 5,000 characters).";
    } else {
        try {
            $insert = $pdo->prepare(
                "INSERT INTO loans (member_id, amount_requested, purpose, loan_status)
                 VALUES (?, ?, ?, 'pending')"
            );
            $insert->execute([$memberId, $amount, $purpose]);
            $_SESSION["loan_csrf_token"] = bin2hex(random_bytes(32));
            header("Location: apply_loan.php?submitted=1");
            exit;
        } catch (PDOException $e) {
            error_log("Loan application failed: " . $e->getMessage());
            $error = "We could not submit your application. Please try again.";
        }
    }
}

if (isset($_GET["submitted"])) {
    $success = "Your loan application was submitted and is awaiting review.";
}

$token = csrf_token("loan_csrf_token");
portal_open("Apply for credit", "apply");
?>

<section class="panel">
    <h2>Loan request</h2>
    <p class="quiet">State the amount and what it is for. Submission does not approve or disburse the loan.</p>

    <?php if ($error !== ""): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success !== ""): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="apply_loan.php">
        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
        <div class="field">
            <label for="amount_requested">Amount (KSh)</label>
            <input type="number" id="amount_requested" name="amount_requested" min="0.01" max="9999999999999.99" step="0.01" required>
        </div>
        <div class="field">
            <label for="purpose">Purpose</label>
            <textarea id="purpose" name="purpose" maxlength="5000" required placeholder="Explain how you intend to use the loan."></textarea>
        </div>
        <button class="btn btn-green" type="submit">Submit application</button>
    </form>
</section>

<?php portal_close(); ?>
