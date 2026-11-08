
<?php
session_start();
require_once "db.php";

// Require a logged-in member.
if (!isset($_SESSION["member_id"])) {
    header("Location: login.php");
    exit;
}

// Only approved members can view their savings.
if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "member"
) {
    header("Location: login.php");
    exit;
}

$memberId = (int) $_SESSION["member_id"];

try {
    // Confirm that the member is still approved.
    $memberStmt = $pdo->prepare("
        SELECT full_name
        FROM members
        WHERE id = ?
          AND role = 'member'
          AND membership_status = 'approved'
    ");
    $memberStmt->execute([$memberId]);
    $member = $memberStmt->fetch();

    if (!$member) {
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit;
    }

    // Calculate this member's recorded savings.
    $totalStmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0.00) AS total_savings
        FROM savings
        WHERE member_id = ?
    ");
    $totalStmt->execute([$memberId]);
    $totalSavings = $totalStmt->fetch()["total_savings"];

    // Retrieve only this member's savings records.
    $historyStmt = $pdo->prepare("
        SELECT amount, transaction_id, created_at
        FROM savings
        WHERE member_id = ?
        ORDER BY created_at DESC
    ");
    $historyStmt->execute([$memberId]);
    $savingsHistory = $historyStmt->fetchAll();

} catch (PDOException $e) {
    error_log("Unable to load member savings: " . $e->getMessage());
    http_response_code(500);
    exit("Unable to load your savings. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Savings | TechLink Wealth</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f6fa;
            color: #172b4d;
            margin: 0;
            padding: 30px 15px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
        }

        .header, .balance-card, .history-card {
            background: white;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
        }

        h1, h2 {
            color: #102747;
        }

        .balance-card {
            background: #102747;
            color: white;
        }

        .balance-card h2 {
            color: #e0b64f;
        }

        .balance {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }

        .note {
            font-size: 14px;
            line-height: 1.6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #102747;
            color: white;
        }

        .empty {
            background: #f3f6fa;
            padding: 16px;
            border-radius: 8px;
        }

        a {
            color: #164e8a;
        }

        .table-wrap {
            overflow-x: auto;
        }
    </style>
</head>

<body>
    <main class="container">
        <section class="header">
            <h1>My Savings</h1>

            <p>
                Welcome,
                <strong><?= htmlspecialchars(
                    $member["full_name"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?></strong>
            </p>

            <a href="dashboard.php">Back to Member Dashboard</a>
            |
            <a href="logout.php">Log Out</a>
        </section>

        <section class="balance-card">
            <h2>Total Recorded Savings</h2>

            <p class="balance">
                KSh <?= number_format((float) $totalSavings, 2) ?>
            </p>

            <p class="note">
                This figure reflects savings entries recorded in the
                database. It does not independently verify that funds
                were received or reconciled.
            </p>
        </section>

        <section class="history-card">
            <h2>Savings History</h2>

            <?php if (count($savingsHistory) > 0): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Amount</th>
                                <th>Date Recorded</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($savingsHistory as $record): ?>
                                <tr>
                                    <td>
                                        <?= (int) $record["transaction_id"] ?>
                                    </td>

                                    <td>
                                        KSh <?= number_format(
                                            (float) $record["amount"],
                                            2
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $record["created_at"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty">
                    You do not have any recorded savings yet.
                </p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
