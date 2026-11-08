
<?php
session_start();

if (!isset($_SESSION["member_id"])) {
    header("Location: login.php");
    exit;
}

$memberName = htmlspecialchars(
    $_SESSION["member_name"] ?? "Member",
    ENT_QUOTES,
    "UTF-8"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard | TechLink Wealth</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body class="dashboard-page">

    <header class="dashboard-header">
        <div class="dashboard-brand">
            <div class="dashboard-logo">TW</div>
            <div>
                <h1>TechLink Wealth</h1>
                <p>Innovate. Invest. Grow.</p>
            </div>
        </div>

        <a class="logout-button" href="logout.php">Log Out</a>
    </header>

    <main class="dashboard-container">
        <section class="welcome-section">
            <p class="eyebrow">MEMBER PORTAL</p>
            <h2>Welcome, <?= $memberName ?>!</h2>
            <p>Manage your membership and explore your account services.</p>
        </section>

        <section class="dashboard-grid">
            <article class="dashboard-card">
                <div class="card-icon">💰</div>
                <h3>My Savings</h3>
                <p>View your savings records and balances.</p>
<a href="my_savings.php">View My Savings</a>            </article>

            <article class="dashboard-card">
                <div class="card-icon">↔</div>
                <h3>Transactions</h3>
                <p>Review deposits and other account activity.</p>
                <a href="my_transactions.php">View My Transactions</a>
            </article>

            <article class="dashboard-card">
                <div class="card-icon">📋</div>
                <h3>My Loans</h3>
                <p>Explore loan applications and their status.</p>
                <a href="my_loans.php">View My Loans</a>
            </article>

            <article class="dashboard-card">
                <div class="card-icon">👤</div>
                <h3>My Profile</h3>
                <p>View your member account details.</p>
                <a href="my_profile.php">View My Profile</a>
            </article>
        </section>

        <div class="dashboard-notice">
            <strong>Account security</strong>
            <p>
                This dashboard is under development. Financial balances
                and transactions are not yet connected to live account records.
            </p>
        </div>
    </main>

    <footer class="dashboard-footer">
        &copy; <?= date("Y") ?> TechLink Wealth Investments Limited
    </footer>

</body>
</html>
