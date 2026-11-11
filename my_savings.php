<?php
require_once "includes/app.php";

$member = require_member(true);
$memberId = (int) $member["id"];

try {
    $totalStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS total_savings FROM savings WHERE member_id = ?");
    $totalStmt->execute([$memberId]);
    $totalSavings = $totalStmt->fetch()["total_savings"];

    $historyStmt = $pdo->prepare(
        "SELECT amount, transaction_id, created_at
         FROM savings
         WHERE member_id = ?
         ORDER BY created_at DESC"
    );
    $historyStmt->execute([$memberId]);
    $savingsHistory = $historyStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Unable to load member savings: " . $e->getMessage());
    http_response_code(500);
    exit("Unable to load your savings. Please try again later.");
}

portal_open("Savings", "savings");
?>

<section class="stats">
    <article class="stat">
        <span>Total recorded</span>
        <strong><?= e(money($totalSavings)) ?></strong>
    </article>
    <article class="stat">
        <span>Entries</span>
        <strong><?= count($savingsHistory) ?></strong>
    </article>
</section>

<section class="panel">
    <h2>Savings history</h2>
    <p class="quiet">Amounts posted to your membership. A row is a book entry, not a bank confirmation.</p>

    <?php if ($savingsHistory): ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Transaction</th>
                        <th class="num">Amount</th>
                        <th>Date recorded</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($savingsHistory as $record): ?>
                        <tr>
                            <td><?= (int) $record["transaction_id"] ?></td>
                            <td class="num"><?= e(money($record["amount"])) ?></td>
                            <td><?= e($record["created_at"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty">You do not have any recorded savings yet.</p>
    <?php endif; ?>
</section>

<?php portal_close(); ?>
