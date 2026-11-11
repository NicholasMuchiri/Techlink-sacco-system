<?php
require_once "includes/app.php";

$member = require_member(true);
$memberId = (int) $member["id"];

try {
    $loanStmt = $pdo->prepare("SELECT * FROM loans WHERE member_id = ? ORDER BY id DESC");
    $loanStmt->execute([$memberId]);
    $loans = $loanStmt->fetchAll();

    $sumStmt = $pdo->prepare(
        "SELECT
            (SELECT COALESCE(SUM(amount_requested), 0) FROM loans
              WHERE member_id = ? AND loan_status IN ('disbursed', 'closed')) AS borrowed,
            (SELECT COALESCE(SUM(amount), 0) FROM transactions
              WHERE member_id = ? AND transaction_type = 'loan_repayment'
                AND transaction_status = 'confirmed') AS repaid"
    );
    $sumStmt->execute([$memberId, $memberId]);
    $sums = $sumStmt->fetch();
    $borrowed = (float) $sums["borrowed"];
    $repaid = (float) $sums["repaid"];
    $outstanding = max(0.0, $borrowed - $repaid);
} catch (PDOException $e) {
    error_log("Unable to load member loans: " . $e->getMessage());
    http_response_code(500);
    exit("Unable to load your loans. Please try again later.");
}

portal_open("My loans", "loans");
?>

<?php if ($borrowed > 0): ?>
<section class="stats">
    <article class="stat"><span>Borrowed</span><strong><?= e(money($borrowed)) ?></strong></article>
    <article class="stat"><span>Repaid</span><strong><?= e(money($repaid)) ?></strong></article>
    <article class="stat"><span>Outstanding</span><strong><?= e(money($outstanding)) ?></strong></article>
</section>
<?php endif; ?>

<section class="panel">
    <h2>Credit records</h2>
    <p class="quiet">Applications stored for your membership. This page does not submit or approve a loan.</p>

    <?php if ($loans): ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <?php foreach (array_keys($loans[0]) as $column): ?>
                            <?php if ($column === "member_id") { continue; } ?>
                            <th><?= e(ucwords(str_replace("_", " ", $column))) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loans as $loan): ?>
                        <tr>
                            <?php foreach ($loan as $column => $value): ?>
                                <?php if ($column === "member_id") { continue; } ?>
                                <td>
                                    <?php if ($column === "loan_status"): ?>
                                        <span class="pill <?= e(strtolower((string) $value)) ?>"><?= e($value) ?></span>
                                    <?php else: ?>
                                        <?= e($value ?? "—") ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty">You do not have any loan records yet.</p>
    <?php endif; ?>

    <p style="margin-top:1rem;">
        <a class="btn btn-green" href="apply_loan.php">Apply for credit</a>
        <?php if ($borrowed > 0): ?>
            <a class="btn btn-line" href="deposit.php">Repay a loan</a>
        <?php endif; ?>
    </p>
</section>

<?php portal_close(); ?>
