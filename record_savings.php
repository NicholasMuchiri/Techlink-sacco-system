
<?php
session_start();
require_once "db.php";

// Only administrators can access this page.
if (
    !isset($_SESSION["member_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.php");
    exit;
}

// Create a CSRF token to protect form submissions.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !isset($_SESSION["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Invalid request token. Refresh the page and try again.");
    }

    $transactionId = filter_input(
        INPUT_POST,
        "transaction_id",
        FILTER_VALIDATE_INT
    );

    if (!$transactionId || $transactionId < 1) {
        $error = "Please select a valid transaction.";
    } else {
        try {
            $pdo->beginTransaction();

            // Lock and recheck the deposit before recording it.
            $stmt = $pdo->prepare("
                SELECT t.id, t.member_id, t.amount
                FROM transactions t
                INNER JOIN members m
                    ON m.id = t.member_id
                WHERE t.id = ?
                  AND t.transaction_type = 'deposit'
                  AND t.transaction_status = 'confirmed'
                  AND m.membership_status = 'approved'
                  AND m.role = 'member'
                FOR UPDATE
            ");
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch();

            if (!$transaction) {
                throw new RuntimeException(
                    "This deposit is not eligible for savings recording."
                );
            }

            $stmt = $pdo->prepare("
                INSERT INTO savings (member_id, transaction_id, amount)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $transaction["member_id"],
                $transaction["id"],
                $transaction["amount"]
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
    $stmt = $pdo->query("
        SELECT
            t.id AS transaction_id,
            t.member_id,
            m.full_name,
            t.amount,
            t.payment_method,
            t.mpesa_receipt,
            t.created_at
        FROM transactions t
        INNER JOIN members m
            ON m.id = t.member_id
        LEFT JOIN savings s
            ON s.transaction_id = t.id
        WHERE t.transaction_type = 'deposit'
          AND t.transaction_status = 'confirmed'
          AND m.membership_status = 'approved'
          AND m.role = 'member'
          AND s.id IS NULL
        ORDER BY t.created_at DESC
    ");

    $eligibleTransactions = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Unable to fetch eligible savings transactions: " . $e->getMessage());
    $eligibleTransactions = [];
    $error = "Unable to load transactions. Please try again.";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Member Savings | TechLink Wealth</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f6fa;
            margin: 0;
            padding: 30px 15px;
            color: #172b4d;
        }

        .container {
            max-width: 1100px;
            margin: auto;
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow-x: auto;
        }

        h1 {
            color: #102747;
        }

        .message {
            color: #176b38;
            background: #e8f7ed;
            padding: 12px;
            border-radius: 6px;
        }

        .error {
            color: #9b1c1c;
            background: #fdeaea;
            padding: 12px;
            border-radius: 6px;
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
        }

        th {
            background: #102747;
            color: white;
        }

        button {
            background: #c99a32;
            color: #102747;
            padding: 10px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            background: #e0b64f;
        }

        a {
            color: #164e8a;
        }

        .empty {
            padding: 20px;
            background: #f3f6fa;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <main class="container">
        <h1>Record Member Savings</h1>
        <p>
            Only confirmed deposit transactions from approved members
            can be recorded.
        </p>

        <p><a href="admin_dashboard.php">Back to Admin Dashboard</a></p>

        <?php if ($message !== ""): ?>
            <p class="message">
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <p class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <?php if (count($eligibleTransactions) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>M-Pesa Receipt</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eligibleTransactions as $transaction): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars(
                                    $transaction["full_name"],
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
                            <td>
                                <?= htmlspecialchars(
                                    $transaction["created_at"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>
                            <td>
                                <form method="POST" action="record_savings.php">
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars(
                                            $_SESSION["csrf_token"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="transaction_id"
                                        value="<?= (int) $transaction["transaction_id"] ?>"
                                    >

                                    <button type="submit">
                                        Record Savings
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="empty">
                No eligible deposit transactions are currently available.
            </p>
        <?php endif; ?>

        <p>
            <strong>Note:</strong> Only verified deposits should be marked
            confirmed in the transactions table. Recording a transaction here
            does not itself verify that money was received.
        </p>
    </main>
</body>
</html>

