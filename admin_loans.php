
<?php
session_start();
require_once "db.php";

if (
    !isset($_SESSION["member_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION["loan_admin_csrf"])) {
    $_SESSION["loan_admin_csrf"] = bin2hex(random_bytes(32));
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    $loanId = filter_input(INPUT_POST, "loan_id", FILTER_VALIDATE_INT);
    $decision = $_POST["decision"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["loan_admin_csrf"], $token)
    ) {
        $error = "Security verification failed. Refresh and try again.";
    } elseif (!$loanId || !in_array($decision, ["approved", "rejected"], true)) {
        $error = "Invalid loan decision.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT id, loan_status
                FROM loans
                WHERE id = ?
                FOR UPDATE
            ");
            $stmt->execute([$loanId]);
            $loan = $stmt->fetch();

            if (!$loan || $loan["loan_status"] !== "pending") {
                $pdo->rollBack();
                $error = "This application no longer has pending status.";
            } else {
                $update = $pdo->prepare("
                    UPDATE loans
                    SET loan_status = ?
                    WHERE id = ?
                      AND loan_status = 'pending'
                ");
                $update->execute([$decision, $loanId]);

                if ($update->rowCount() !== 1) {
                    throw new RuntimeException("Loan status was not updated.");
                }

                $pdo->commit();

                $_SESSION["loan_admin_csrf"] = bin2hex(random_bytes(32));
                header("Location: admin_loans.php?updated=1");
                exit;
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log("Admin loan decision failed: " . $e->getMessage());
            $error = "Unable to update the application. Please try again.";
        }
    }
}

try {
    $stmt = $pdo->query("
        SELECT
            loans.id,
            loans.member_id,
            members.full_name,
            members.email,
            loans.amount_requested,
            loans.purpose,
            loans.loan_status,
            loans.requested_at
        FROM loans
        INNER JOIN members ON members.id = loans.member_id
        ORDER BY loans.requested_at DESC
    ");
    $loans = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Unable to load loan applications: " . $e->getMessage());
    http_response_code(500);
    exit("Unable to load loan applications.");
}

function adminLoanEscape($value) {
    return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Management | TechLink Wealth</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 25px 15px;
            background: #f3f6fb;
            color: #243247;
            font-family: Arial, sans-serif;
        }

        .container { max-width: 1200px; margin: auto; }

        header {
            padding: 24px;
            background: #12376a;
            color: white;
            border-radius: 12px 12px 0 0;
        }

        header h1 { margin: 0 0 8px; }

        .panel {
            padding: 22px;
            background: white;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 5px 18px rgba(0,0,0,.07);
        }

        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5eaf1;
            vertical-align: top;
        }

        th { background: #edf2f9; }

        .purpose { min-width: 180px; white-space: pre-wrap; }

        .status { font-weight: bold; text-transform: capitalize; }

        button {
            padding: 8px 11px;
            margin: 3px;
            border: 0;
            border-radius: 5px;
            color: white;
            cursor: pointer;
        }

        .approve { background: #15803d; }
        .reject { background: #b91c1c; }

        .message {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 7px;
        }

        .error { background: #fee2e2; color: #991b1b; }
        .success { background: #dcfce7; color: #166534; }

        .back {
            display: inline-block;
            margin-bottom: 18px;
            color: #12376a;
        }
    </style>
</head>
<body>
<main class="container">
    <header>
        <h1>Loan Management</h1>
        <p>Review and manage member loan applications.</p>
    </header>

    <section class="panel">
        <a class="back" href="admin_dashboard.php">← Back to Admin Dashboard</a>

        <?php if ($error !== ""): ?>
            <div class="message error"><?= adminLoanEscape($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET["updated"])): ?>
            <div class="message success">Loan application status updated.</div>
        <?php endif; ?>

        <?php if (!$loans): ?>
            <p>No loan applications have been submitted yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Member</th>
                        <th>Amount (KSh)</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td><?= adminLoanEscape($loan["id"]) ?></td>
                            <td>
                                <?= adminLoanEscape($loan["full_name"]) ?><br>
                                <small><?= adminLoanEscape($loan["email"]) ?></small>
                            </td>
                            <td><?= number_format((float) $loan["amount_requested"], 2) ?></td>
                            <td class="purpose"><?= adminLoanEscape($loan["purpose"]) ?></td>
                            <td class="status"><?= adminLoanEscape($loan["loan_status"]) ?></td>
                            <td><?= adminLoanEscape($loan["requested_at"]) ?></td>
                            <td>
                                <?php if ($loan["loan_status"] === "pending"): ?>
                                    <form method="POST" action="admin_loans.php"
                                          onsubmit="return confirm('Confirm this loan decision?');">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= adminLoanEscape($_SESSION["loan_admin_csrf"]) ?>">
                                        <input type="hidden" name="loan_id"
                                               value="<?= adminLoanEscape($loan["id"]) ?>">
                                        <button class="approve" name="decision"
                                                value="approved" type="submit">Approve</button>
                                        <button class="reject" name="decision"
                                                value="rejected" type="submit">Reject</button>
                                    </form>
                                <?php else: ?>
                                    No action available
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
