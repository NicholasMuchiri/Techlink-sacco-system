<?php
require_once "includes/app.php";
require_admin();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $txnId = filter_input(INPUT_POST, "transaction_id", FILTER_VALIDATE_INT);
    $action = $_POST["action"] ?? "";

    if (!csrf_valid("txn_admin_csrf", $_POST["csrf_token"] ?? "")) {
        $error = "This action expired. Refresh the page and try again.";
    } elseif (!$txnId || !in_array($action, ["confirm", "fail"], true)) {
        $error = "That transaction action is not valid.";
    } else {
        $newStatus = $action === "confirm" ? "confirmed" : "failed";
        try {
            $upd = $pdo->prepare(
                "UPDATE transactions SET transaction_status = ?
                 WHERE id = ? AND transaction_status = 'pending'"
            );
            $upd->execute([$newStatus, $txnId]);
            $_SESSION["txn_admin_csrf"] = bin2hex(random_bytes(32));
            header("Location: admin_transactions.php?updated=" . ($upd->rowCount() === 1 ? "1" : "0"));
            exit;
        } catch (PDOException $e) {
            error_log("Transaction decision failed: " . $e->getMessage());
            $error = "Unable to update the transaction. Please try again.";
        }
    }
}

try {
    $stmt = $pdo->query(
        "SELECT
            t.id,
            m.full_name,
            t.transaction_type,
            t.amount,
            t.payment_method,
            t.mpesa_receipt,
            t.transaction_status,
            t.created_at,
            (SELECT COUNT(*) FROM savings s WHERE s.transaction_id = t.id) AS posted
         FROM transactions t
         INNER JOIN members m ON m.id = t.member_id
         ORDER BY t.created_at DESC"
    );
    $transactions = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Unable to load transactions: " . $e->getMessage());
    http_response_code(500);
    exit("Unable to load transactions. Please try again later.");
}

$token = csrf_token("txn_admin_csrf");
portal_open("Transactions", "transactions");
?>

<section class="panel">
    <h2>Society transactions</h2>
    <p class="quiet">A stored row is not proof that a payment was received. Confirm a deposit only after checking the M-Pesa receipt or cash.</p>

    <?php if ($error !== ""): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php elseif (($_GET["updated"] ?? "") === "1"): ?>
        <div class="alert alert-success">Transaction updated. Confirmed deposits can now be posted to savings.</div>
    <?php elseif (($_GET["updated"] ?? "") === "0"): ?>
        <div class="alert alert-error">That transaction is no longer pending.</div>
    <?php endif; ?>

    <?php if ($transactions): ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Member</th>
                        <th>Type</th>
                        <th class="num">Amount</th>
                        <th>Method</th>
                        <th>M-Pesa receipt</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <?php $status = strtolower((string) $transaction["transaction_status"]); ?>
                        <tr>
                            <td><?= (int) $transaction["id"] ?></td>
                            <td><?= e($transaction["full_name"]) ?></td>
                            <td><?= e($transaction["transaction_type"]) ?></td>
                            <td class="num"><?= e(money($transaction["amount"])) ?></td>
                            <td><?= e($transaction["payment_method"]) ?></td>
                            <td><?= e($transaction["mpesa_receipt"] ?? "—") ?></td>
                            <td><span class="pill <?= e($status) ?>"><?= e($status) ?></span></td>
                            <td><?= e($transaction["created_at"]) ?></td>
                            <td>
                                <?php if ($status === "pending"): ?>
                                    <form method="POST" action="admin_transactions.php">
                                        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                        <input type="hidden" name="transaction_id" value="<?= (int) $transaction["id"] ?>">
                                        <div class="inline-actions">
                                            <button class="btn btn-ok" name="action" value="confirm" type="submit">Confirm</button>
                                            <button class="btn btn-danger" name="action" value="fail" type="submit">Reject</button>
                                        </div>
                                    </form>
                                <?php elseif ($status === "confirmed" && $transaction["transaction_type"] === "deposit" && (int) $transaction["posted"] === 0): ?>
                                    <a class="btn btn-gold" href="record_savings.php">Post to savings</a>
                                <?php elseif ((int) $transaction["posted"] > 0): ?>
                                    <span class="note">Posted to savings</span>
                                <?php else: ?>
                                    <span class="note">Recorded</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty">No transactions have been recorded yet.</p>
    <?php endif; ?>
</section>

<?php portal_close(); ?>
