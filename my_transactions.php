<?php
require_once "includes/app.php";

$member = require_member(true);
$memberId = (int) $member["id"];

try {
    $stmt = $pdo->prepare(
        "SELECT id, transaction_type, amount, payment_method, mpesa_receipt, transaction_status, created_at
         FROM transactions
         WHERE member_id = ?
         ORDER BY created_at DESC"
    );
    $stmt->execute([$memberId]);
    $transactions = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Unable to load member transactions: " . $e->getMessage());
    http_response_code(500);
    exit("Unable to load your transactions. Please try again later.");
}

portal_open("Transactions", "transactions");
?>

<section class="panel">
    <h2>Account activity</h2>
    <p class="quiet">These rows are stored against your membership. They do not by themselves prove that funds were received.</p>

    <?php if ($transactions): ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th class="num">Amount</th>
                        <th>Method</th>
                        <th>M-Pesa receipt</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <?php $status = strtolower((string) $transaction["transaction_status"]); ?>
                        <tr>
                            <td><?= (int) $transaction["id"] ?></td>
                            <td><?= e($transaction["transaction_type"]) ?></td>
                            <td class="num"><?= e(money($transaction["amount"])) ?></td>
                            <td><?= e($transaction["payment_method"]) ?></td>
                            <td><?= e($transaction["mpesa_receipt"] ?? "—") ?></td>
                            <td><span class="pill <?= e($status) ?>"><?= e($status) ?></span></td>
                            <td><?= e($transaction["created_at"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty">You do not have any transactions yet.</p>
    <?php endif; ?>
</section>

<?php portal_close(); ?>
