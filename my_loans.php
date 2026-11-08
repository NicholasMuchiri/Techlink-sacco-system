
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

    // Retrieve only this member's loan records.
    $loanStmt = $pdo->prepare("
        SELECT *
        FROM loans
        WHERE member_id = ?
        ORDER BY created_at DESC
    ");
    $loanStmt->execute([$memberId]);
    $loans = $loanStmt->fetchAll();

} catch (PDOException $e) {
    error_log("Unable to load member loans: " . $e->getMessage());
    http_response_code(500);
    exit("Unable to load your loans. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Loans | TechLink Wealth</title>

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

        .note {
            margin-top: 25px;
            font-size: 14px;
            line-height: 1.6;
        }

        .table-wrap {
            overflow-x: auto;
        }
    </style>
</head>

<body>
    <main class="container">
        <h1>My Loans</h1>

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

        <?php if (count($loans) > 0): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <?php foreach (array_keys($loans[0]) as $column): ?>
                                <th>
                                    <?= htmlspecialchars(
                                        ucwords(str_replace("_", " ", $column)),
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($loans as $loan): ?>
                            <tr>
                                <?php foreach ($loan as $value): ?>
                                    <td>
                                        <?= htmlspecialchars(
                                            (string) ($value ?? "—"),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="empty">
                You do not have any loan records yet.
            </p>
        <?php endif; ?>

        <p class="note">
            <strong>Note:</strong> This page displays loan records stored
            in the database. It does not submit loan applications or approve
            loans.
        </p>
    </main>
</body>
</html>
