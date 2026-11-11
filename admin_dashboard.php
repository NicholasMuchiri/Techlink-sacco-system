<?php
require_once "includes/app.php";
require_admin();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $memberId = filter_input(INPUT_POST, "member_id", FILTER_VALIDATE_INT);
    $action = $_POST["action"] ?? "";

    if (!csrf_valid("membership_csrf", $_POST["csrf_token"] ?? "")) {
        $error = "This action expired. Refresh the page and try again.";
    } elseif (!$memberId || !in_array($action, ["approve", "reject"], true)) {
        $error = "That membership action is not valid.";
    } else {
        $newStatus = $action === "approve" ? "approved" : "rejected";
        $stmt = $pdo->prepare(
            "UPDATE members
             SET membership_status = ?
             WHERE id = ?
               AND role = 'member'
               AND membership_status = 'pending'"
        );
        $stmt->execute([$newStatus, $memberId]);
        $_SESSION["membership_csrf"] = bin2hex(random_bytes(32));
        header("Location: admin_dashboard.php?updated=1");
        exit;
    }
}

$members = $pdo->query(
    "SELECT id, full_name, email, phone, membership_status, created_at
     FROM members
     WHERE role = 'member'
     ORDER BY created_at DESC"
)->fetchAll();

$counts = ["pending" => 0, "approved" => 0, "rejected" => 0];
foreach ($members as $member) {
    $key = strtolower((string) $member["membership_status"]);
    if (isset($counts[$key])) {
        $counts[$key]++;
    }
}

$pendingLoans = 0;
$pendingDeposits = 0;
$unposted = 0;
try {
    $pendingLoans = (int) $pdo->query("SELECT COUNT(*) FROM loans WHERE loan_status = 'pending'")->fetchColumn();
    $pendingDeposits = (int) $pdo->query(
        "SELECT COUNT(*) FROM transactions WHERE transaction_type = 'deposit' AND transaction_status = 'pending'"
    )->fetchColumn();
    $unposted = (int) $pdo->query(
        "SELECT COUNT(*) FROM transactions t
         INNER JOIN members m ON m.id = t.member_id
         LEFT JOIN savings s ON s.transaction_id = t.id
         WHERE t.transaction_type = 'deposit' AND t.transaction_status = 'confirmed'
           AND m.membership_status = 'approved' AND m.role = 'member' AND s.id IS NULL"
    )->fetchColumn();
} catch (PDOException $e) {
    error_log("Admin overview counts failed: " . $e->getMessage());
}

$token = csrf_token("membership_csrf");
portal_open("Members", "members");
?>

<?php if ($error !== ""): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php elseif (isset($_GET["updated"])): ?>
    <div class="alert alert-success">Membership status updated.</div>
<?php endif; ?>

<section class="stats">
    <article class="stat"><span>Pending</span><strong><?= (int) $counts["pending"] ?></strong></article>
    <article class="stat"><span>Approved</span><strong><?= (int) $counts["approved"] ?></strong></article>
    <article class="stat"><span>Declined</span><strong><?= (int) $counts["rejected"] ?></strong></article>
</section>

<section class="cards">
    <a class="menu-card" href="admin_transactions.php">
        <h3>Transactions</h3>
        <p><?= $pendingDeposits ?> deposit(s) waiting for confirmation.</p>
        <span>Review deposits</span>
    </a>
    <a class="menu-card" href="record_savings.php">
        <h3>Record deposit</h3>
        <p><?= $unposted ?> confirmed deposit(s) not yet in the savings book.</p>
        <span>Post to savings</span>
    </a>
    <a class="menu-card" href="admin_loans.php">
        <h3>Credit book</h3>
        <p><?= $pendingLoans ?> loan application(s) pending.</p>
        <span>Decide loans</span>
    </a>
    <a class="menu-card" href="admin_savings.php">
        <h3>Savings book</h3>
        <p>Balances by approved member.</p>
        <span>View balances</span>
    </a>
</section>

<section class="panel">
    <h2>Membership register</h2>
    <p class="quiet">Approve or decline applications that are still pending.</p>

    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Decision</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$members): ?>
                <tr><td colspan="6">No members have registered yet.</td></tr>
            <?php else: ?>
                <?php foreach ($members as $member): ?>
                    <?php $status = strtolower((string) $member["membership_status"]); ?>
                    <tr>
                        <td><?= e($member["full_name"]) ?></td>
                        <td><?= e($member["email"]) ?></td>
                        <td><?= e($member["phone"]) ?></td>
                        <td><span class="pill <?= e($status) ?>"><?= e($status) ?></span></td>
                        <td><?= e($member["created_at"]) ?></td>
                        <td>
                            <?php if ($status === "pending"): ?>
                                <div class="inline-actions">
                                    <form method="POST" action="admin_dashboard.php">
                                        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                        <input type="hidden" name="member_id" value="<?= (int) $member["id"] ?>">
                                        <button class="btn btn-ok" type="submit" name="action" value="approve">Approve</button>
                                    </form>
                                    <form method="POST" action="admin_dashboard.php">
                                        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                        <input type="hidden" name="member_id" value="<?= (int) $member["id"] ?>">
                                        <button class="btn btn-danger" type="submit" name="action" value="reject">Decline</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="note">Recorded</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php portal_close(); ?>
