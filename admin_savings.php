<?php
require_once "includes/app.php";
require_admin();

$stmt = $pdo->query(
    "SELECT
        m.id AS member_id,
        m.full_name,
        m.email,
        COUNT(s.id) AS savings_records,
        COALESCE(SUM(s.amount), 0.00) AS total_savings
     FROM members m
     LEFT JOIN savings s ON s.member_id = m.id
     WHERE m.role = 'member'
       AND m.membership_status = 'approved'
     GROUP BY m.id, m.full_name, m.email
     ORDER BY m.full_name ASC"
);
$members = $stmt->fetchAll();

$totalStmt = $pdo->query(
    "SELECT COALESCE(SUM(s.amount), 0.00) AS grand_total
     FROM savings s
     INNER JOIN members m ON m.id = s.member_id
     WHERE m.role = 'member'
       AND m.membership_status = 'approved'"
);
$grandTotal = $totalStmt->fetch()["grand_total"];

portal_open("Savings book", "savings");
?>

<section class="stats">
    <article class="stat">
        <span>Society savings on record</span>
        <strong><?= e(money($grandTotal)) ?></strong>
    </article>
    <article class="stat">
        <span>Approved members</span>
        <strong><?= count($members) ?></strong>
    </article>
</section>

<section class="panel">
    <h2>Balances by member</h2>
    <p class="quiet">Totals of savings rows. They do not confirm that cash has been reconciled.</p>
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Email</th>
                    <th class="num">Records</th>
                    <th class="num">Recorded savings</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$members): ?>
                <tr><td colspan="4">No approved members found.</td></tr>
            <?php else: ?>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?= e($member["full_name"]) ?></td>
                        <td><?= e($member["email"]) ?></td>
                        <td class="num"><?= (int) $member["savings_records"] ?></td>
                        <td class="num"><?= e(money($member["total_savings"])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php portal_close(); ?>
