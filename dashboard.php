<?php
require_once "includes/app.php";

if (is_logged_in() && $_SESSION["role"] === "admin") {
    header("Location: admin_dashboard.php");
    exit;
}

$member = require_member(false);
$memberId = (int) $member["id"];
$totalSavings = 0.0;
$loanCount = 0;
$transactionCount = 0;

try {
    $savingsStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM savings WHERE member_id = ?");
    $savingsStmt->execute([$memberId]);
    $totalSavings = (float) $savingsStmt->fetch()["total"];

    $loanStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM loans WHERE member_id = ?");
    $loanStmt->execute([$memberId]);
    $loanCount = (int) $loanStmt->fetch()["total"];

    $txnStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM transactions WHERE member_id = ?");
    $txnStmt->execute([$memberId]);
    $transactionCount = (int) $txnStmt->fetch()["total"];
} catch (PDOException $e) {
    error_log("Member overview failed: " . $e->getMessage());
}

$status = strtolower((string) $member["membership_status"]);
portal_open("Overview", "home");
?>

<?php if ($status !== "approved" || isset($_GET["notice"])): ?>
    <div class="alert <?= $status === "rejected" ? "alert-error" : "" ?>">
        Membership status: <strong><?= e(ucfirst($status)) ?></strong>.
        Savings history and new credit applications stay closed until the society approves the account.
    </div>
<?php endif; ?>

<section class="stats">
    <article class="stat">
        <span>Recorded savings</span>
        <strong><?= e(money($totalSavings)) ?></strong>
    </article>
    <article class="stat">
        <span>Transactions</span>
        <strong><?= $transactionCount ?></strong>
    </article>
    <article class="stat">
        <span>Loan records</span>
        <strong><?= $loanCount ?></strong>
    </article>
</section>

<section class="cards">
    <a class="menu-card" href="my_savings.php">
        <h3>Savings</h3>
        <p>Balances and the entries posted to your membership.</p>
        <span>Open book</span>
    </a>
    <a class="menu-card" href="my_transactions.php">
        <h3>Transactions</h3>
        <p>Deposits and other activity stored on your account.</p>
        <span>View activity</span>
    </a>
    <a class="menu-card" href="my_loans.php">
        <h3>Loans</h3>
        <p>Applications you have submitted and their current status.</p>
        <span>View credit</span>
    </a>
    <a class="menu-card" href="deposit.php">
        <h3>Make a deposit</h3>
        <p>Report an M-Pesa or cash deposit for the society to confirm.</p>
        <span>New deposit</span>
    </a>
    <a class="menu-card" href="apply_loan.php">
        <h3>Apply for credit</h3>
        <p>Request an amount and state what the funds are for.</p>
        <span>New application</span>
    </a>
    <a class="menu-card" href="my_profile.php">
        <h3>Profile</h3>
        <p>Your register details, phone number and password.</p>
        <span>Manage account</span>
    </a>
</section>

<p class="note" style="margin-top:1rem;">
    Figures are totals of records in the society books. They do not independently confirm that money was received.
</p>

<?php portal_close(); ?>
