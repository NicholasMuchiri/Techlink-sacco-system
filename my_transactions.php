
<?php
session_start();
require_once "db.php";

// Require a logged-in member.
if (!isset($_SESSION["member_id"])) {
    header("Location: login.php");
    exit;
}

// Allow approved members only.
if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "member"
) {
    header("Location: login.php");
    exit;
}

$memberId = (int) $_SESSION["member_id"];

try {
    // Confirm the member is still approved.
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

    // Retrieve only this member's transactions.
    $stmt = $pdo->prepare("
        SELECT
            id,
            transaction_type,
            amount,
            payment_method,
            mpesa_receipt,
            transaction_status,
            created_at
        FROM transactions
        WHERE member_id = ?
        ORDER BY created_at DESC
    ");

    $stmt->execute([$memberId]);
    $transactions = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Unable to load member transactions: " . $e->getMessage());
    http_response_code(500);
    exit("Unable to load your transactions. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Transactions | TechLink Wealth</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f6fa;
            color: #172b4d;
            margin: 0;
            padding: 30px 15px;
        }

        .container {
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            overflow-x: auto;
        }

        h1 {
            color: #102747;
        }

        .back-link {
            display: inline-block;
            margin: 10px 0 20px;
            color: #164e8a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            white-space: nowrap;
        }

        th {
            background: #102747;
            color: white;
        }

        tr:hover {
            background: #f5f8fc;
        }

        .status {
            font-weight: bold;
            text-transform: capitalize;
        }

        .empty {
            background: #f3f6fa;
            padding: 18px;
            border-radius: 8px;
        }

        .note {
            margin-top: 25px;
            font-size: 14px;
            line-height: 1.6;
        }
    </style>
</head>

<body>
    <main class="container">
        <h1>My Transactions</h1>

        <p>
            Welcome,
            <strong><?= htmlspecialchars(
                $member["full_name"],
                ENT_QUOTES,
                "UTF-8"
            ) ?></strong>
        </p>

        <a class="back-link" href="dashboard.php">
            &larr; Back to Member Dashboard
        </a>

        <?php if (count($transactions) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>M-Pesa Receipt</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td>
                                <?= (int) $transaction["id"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $transaction["transaction_type"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                KSh <?= number_format(
                                    (float) $transaction["amount"],
                                    2
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $transaction["payment_method"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $transaction["mpesa_receipt"] ?? "—",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td class="status">
                                <?= htmlspecialchars(
                                    $transaction["transaction_status"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $transaction["created_at"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php else: ?>
            <p class="empty">
                You do not have any transactions yet.
            </p>
        <?php endif; ?>

        <p class="note">
            <strong>Note:</strong> Transaction records show information
            stored in the database. A record alone does not prove that
            funds were received.
        </p>
    </main>
</body>
</html>
