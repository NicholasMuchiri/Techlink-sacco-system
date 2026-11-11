<?php
require_once "includes/app.php";

$member = require_member(true);
$memberId = (int) $member["id"];
$error = "";
$success = "";
$type = $_POST["transaction_type"] ?? ($_GET["type"] ?? "deposit");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $amount = trim($_POST["amount"] ?? "");
    $method = $_POST["payment_method"] ?? "";
    $reference = strtoupper(trim($_POST["mpesa_receipt"] ?? ""));

    if (!csrf_valid("deposit_csrf", $_POST["csrf_token"] ?? "")) {
        $error = "Your session verification failed. Refresh the page and try again.";
    } elseif (!in_array($type, ["deposit", "loan_repayment"], true)) {
        $error = "Choose what the payment is for.";
    } elseif (
        !preg_match('/^[0-9]{1,13}(\.[0-9]{1,2})?$/', $amount) ||
        (float) $amount <= 0
    ) {
        $error = "Enter a valid amount greater than zero.";
    } elseif (!in_array($method, ["mpesa", "bank", "cash"], true)) {
        $error = "Choose how the payment was made.";
    } elseif ($method === "mpesa" && !preg_match('/^[A-Z0-9]{10}$/', $reference)) {
        $error = "Enter the 10-character M-Pesa receipt code from your confirmation message.";
    } elseif ($method === "bank" && !preg_match('/^[A-Z0-9]{6,20}$/', $reference)) {
        $error = "Enter the bank slip or transfer reference (6-20 letters or digits).";
    } else {
        try {
            if ($method !== "cash") {
                $dup = $pdo->prepare("SELECT id FROM transactions WHERE mpesa_receipt = ? LIMIT 1");
                $dup->execute([$reference]);
                if ($dup->fetch()) {
                    $error = "That reference has already been submitted.";
                }
            }

            if ($error === "" && $type === "loan_repayment") {
                $loanCheck = $pdo->prepare("SELECT id FROM loans WHERE member_id = ? AND loan_status = 'disbursed' LIMIT 1");
                $loanCheck->execute([$memberId]);
                if (!$loanCheck->fetch()) {
                    $error = "You have no disbursed loan to repay.";
                }
            }

            if ($error === "") {
                $insert = $pdo->prepare(
                    "INSERT INTO transactions (member_id, transaction_type, amount, payment_method, mpesa_receipt, transaction_status)
                     VALUES (?, ?, ?, ?, ?, 'pending')"
                );
                $insert->execute([$memberId, $type, $amount, $method, $method === "cash" ? null : $reference]);
                $_SESSION["deposit_csrf"] = bin2hex(random_bytes(32));
                header("Location: deposit.php?submitted=1");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Payment submission failed: " . $e->getMessage());
            $error = "We could not submit your payment. Please try again.";
        }
    }
}

if (isset($_GET["submitted"])) {
    $success = "Your payment was submitted. It counts once the society confirms it.";
}

$token = csrf_token("deposit_csrf");
portal_open("Make a deposit", "deposit");
?>

<section class="panel">
    <h2>Report a payment</h2>
    <p class="quiet">Pay the society first, then enter the details here. Nothing changes on your account until an administrator confirms the payment.</p>

    <?php if ($error !== ""): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success !== ""): ?>
        <div class="alert alert-success">
            <?= e($success) ?>
            <p><a href="my_transactions.php">View my transactions</a></p>
        </div>
    <?php endif; ?>

    <form method="POST" action="deposit.php">
        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
        <div class="field">
            <label for="transaction_type">Payment is for</label>
            <select id="transaction_type" name="transaction_type" required>
                <option value="deposit" <?= $type === "deposit" ? "selected" : "" ?>>Savings deposit</option>
                <option value="loan_repayment" <?= $type === "loan_repayment" ? "selected" : "" ?>>Loan repayment</option>
            </select>
        </div>
        <div class="field">
            <label for="amount">Amount (KSh)</label>
            <input type="number" id="amount" name="amount" min="0.01" step="0.01" required>
        </div>
        <div class="field">
            <label for="payment_method">Payment method</label>
            <select id="payment_method" name="payment_method" required>
                <option value="mpesa">M-Pesa</option>
                <option value="bank">Bank</option>
                <option value="cash">Cash</option>
            </select>
        </div>
        <div class="field">
            <label for="mpesa_receipt">M-Pesa receipt code or bank reference</label>
            <input type="text" id="mpesa_receipt" name="mpesa_receipt" maxlength="20" placeholder="e.g. SJK4L9X2QP (leave blank for cash)">
        </div>
        <button class="btn btn-green" type="submit">Submit payment</button>
    </form>
</section>

<?php portal_close(); ?>
