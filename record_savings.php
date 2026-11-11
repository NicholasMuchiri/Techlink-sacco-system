<?php
require_once "includes/app.php";
require_admin();

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_valid("savings_csrf", $_POST["csrf_token"] ?? "")) {
        http_response_code(403);
        exit("Invalid request token. Refresh the page and try again.");
    }

    $transactionId = filter_input(INPUT_POST, "transaction_id", FILTER_VALIDATE_INT);

    if (!$transactionId || $transactionId < 1) {
        $error = "Please select a valid transaction.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "SELECT t.id, t.member_id, t.amount
                 FROM transactions t
                 INNER JOIN members m ON m.id = t.member_id
                 WHERE t.id = ?
                   AND t.transaction_type = 'deposit'
                   AND t.transaction_status = 'confirmed'
                   AND m.membership_status = 'approved'
                   AND m.role = 'member'
                 FOR UPDATE"
            );
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch();

            if (!$transaction) {
                throw new RuntimeException("This deposit is not eligible for savings recording.");
            }

            $insert = $pdo->prepare(
                "INSERT INTO savings (member_id, transaction_id, amount) VALUES (?, ?, ?)"
            );
            $insert->execute([
                $transaction["member_id"],
                $transaction["id"],
                $transaction["amount"],
            ]);

            $pdo->commit();
            $message = "Savings recorded successfully.";
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e->getCode() === "23000") {
                $error = "This transaction may already be recorded.";
            } else {
                error_log("Savings recording failed: " . $e->getMessage());
                $error = "Unable to record savings. Please try again.";
            }
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}

try {
    $stmt = $pdo->query(
        "SELECT
            t.id AS transaction_id,
            m.full_name,
            t.amount,
            t.payment_method,
            t.mpesa_receipt,
            t.created_at
         FROM transactions t
         INNER JOIN members m ON m.id = t.member_id
         LEFT JOIN savings s ON s.transaction_id = t.id
         WHERE t.transaction_type = 'deposit'
           AND t.transaction_status = 'confirmed'
           AND m.membership_status = 'approved'
           AND m.role = 'member'
           AND s.id IS NULL
         ORDER BY t.created_at DESC"
    );
    $eligibleTransactions = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Unable to fetch eligible savings transactions: " . $e->getMessage());
    $eligibleTransactions = [];
    $error = "Unable to load transactions. Please try again.";
}

$token = csrf_token("savings_csrf");
portal_open("Record deposit", "record");
?>

<section class="panel">
    <h2>Eligible confirmed deposits</h2>
    <p class="quiet">Only confirmed deposits from approved members that are not already in the savings book.</p>

    <?php if ($message !== ""): ?>
        <div class="alert alert-success"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if ($error !== ""): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($eligibleTransactions): ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th class="num">Amount</th>
                        <th>Method</th>
                        <th>M-Pesa receipt</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eligibleTransactions as $transaction): ?>
                        <tr>
                            <td><?= e($transaction["full_name"]) ?></td>
                            <td class="num"><?= e(money($transaction["amount"])) ?></td>
                            <td><?= e($transaction["payment_method"]) ?></td>
                            <td><?= e($transaction["mpesa_receipt"] ?? "—") ?></td>
                            <td><?= e($transaction["created_at"]) ?></td>
                            <td>
                                <form method="POST" action="record_savings.php">
                                    <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                    <input type="hidden" name="transaction_id" value="<?= (int) $transaction["transaction_id"] ?>">
                                    <button class="btn btn-gold" type="submit">Post to savings</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty">No eligible deposit transactions are currently available.</p>
    <?php endif; ?>
</section>

<?php portal_close(); ?>
