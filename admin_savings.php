
<?php
session_start();
require_once "db.php";

// Protect this page: administrators only.
if (
    !isset($_SESSION["member_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.php");
    exit;
}

// Get each member's recorded savings.
$stmt = $pdo->query("
    SELECT
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
    ORDER BY m.full_name ASC
");

$members = $stmt->fetchAll();

// Calculate the total of recorded savings amounts.
$totalStmt = $pdo->query("
    SELECT COALESCE(SUM(s.amount), 0.00) AS grand_total
    FROM savings s
    INNER JOIN members m ON m.id = s.member_id
    WHERE m.role = 'member'
      AND m.membership_status = 'approved'
");

$grandTotal = $totalStmt->fetch()["grand_total"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Savings | TechLink Wealth</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, sans-serif;
            background: #f3f5f9;
            color: #17233c;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        header {
            background: #102747;
            color: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        header h1 {
            margin-top: 0;
        }

        .summary {
            background: white;
            border-left: 5px solid #c99a32;
            padding: 22px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .summary p {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 0;
        }

        .table-wrapper {
            background: white;
            border-radius: 12px;
            padding: 15px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 14px;
            border-bottom: 1px solid #e1e6ee;
        }

        th {
            background: #eaf0f8;
        }

        .note {
            color: #586579;
            font-size: 14px;
            line-height: 1.6;
        }

        a {
            color: #174c8c;
        }
    </style>
</head>

<body>
<div class="container">

    <header>
        <h1>TechLink Wealth</h1>
        <p>Member Savings Management</p>
    </header>

    <p><a href="admin_dashboard.php">← Back to Admin Dashboard</a></p>

    <div class="summary">
        <h2>Total Recorded Savings</h2>
        <p>KSh <?= number_format((float)$grandTotal, 2) ?></p>
    </div>

    <h2>Approved Members</h2>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Email</th>
                    <th>Number of Records</th>
                    <th>Recorded Savings</th>
                </tr>
            </thead>

            <tbody>
            <?php if (empty($members)): ?>
                <tr>
                    <td colspan="4">No approved members found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?= htmlspecialchars($member["full_name"]) ?></td>
                        <td><?= htmlspecialchars($member["email"]) ?></td>
                        <td><?= (int)$member["savings_records"] ?></td>
                        <td>
                            KSh <?= number_format(
                                (float)$member["total_savings"], 2
                            ) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p class="note">
        These figures show recorded database entries only.
        They do not confirm that money has been received or reconciled.
    </p>

</div>
</body>
</html>
