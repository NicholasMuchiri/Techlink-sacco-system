
<?php
session_start();
require_once "db.php";

// Allow administrators only.
if (
    !isset($_SESSION["member_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.php");
    exit;
}

// Retrieve transactions and their member details.
try {
    $stmt = $pdo->query("
        SELECT
            t.id,
            m.full_name,
            t.transaction_type,
            t.amount,
            t.payment_method,
            t.mpesa_receipt,
            t.transaction_status,
            t.created_at
        FROM transactions t
        INNER JOIN members m
            ON m.id = t.member_id
        ORDER BY t.created_at DESC
    ");

    $transactions = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Unable to load transactions: " . $e->getMessage());
    http_response_code(500);
    exit("Unable to load transactions. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions | TechLink Wealth</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f6fa;
            color: #172b4d;
            margin: 0;
            padding: 30px 15px;
        }

        .container {
            max-width: 1250px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
        }

        h1 {
            color: #102747;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
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

        .empty {
            background: #f3f6fa;
            padding: 18px;
            border-radius: 8px;
        }

        .status {
            font-weight: bold;
            text-transform: capitalize;
        }
    </style>
</head>

<body>
    <main class="container">
        <h1>Transactions Management</h1>

        <p>View financial transactions recorded in the database.</p>

        <a class="back-link" href="admin_dashboard.php">
            &larr; Back to Admin Dashboard
        </a>

        <?php if (count($transactions) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Member</th>
                        <th>Transaction Type</th>
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
                                    $transaction["full_name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
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
                No transactions have been recorded yet.
            </p>
        <?php endif; ?>

        <p>
            <strong>Important:</strong> These are database records.
            Their presence alone does not prove that a payment was received.
        </p>
    </main>
</body>
</html>
