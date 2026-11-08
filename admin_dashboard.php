
<?php
session_start();
require_once "db.php";

// Only logged-in administrators can access this page.
if (
    !isset($_SESSION["member_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.php");
    exit;
}

// Fetch all registered members.
$stmt = $pdo->query(
    "SELECT id, full_name, email, phone,
            membership_status, created_at
     FROM members
     WHERE role = 'member'
     ORDER BY created_at DESC"
);

$members = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechLink Admin Dashboard</title>
    <style>
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

        .table-wrapper {
            overflow-x: auto;
            background: white;
            padding: 15px;
            border-radius: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #e1e6ee;
        }

        th {
            background: #eaf0f8;
        }

        .pending {
            color: #b77900;
            font-weight: bold;
        }

        .approved {
            color: #16804a;
            font-weight: bold;
        }

        .rejected {
            color: #c62828;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>TechLink Wealth</h1>
        <p>Administrator Dashboard</p>
        <p>Manage member applications and membership status.</p>
    </header>

    <h2>Registered Members</h2>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Registered</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($members)): ?>
                <tr>
                    <td colspan="5">No ordinary members registered yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?= htmlspecialchars($member["full_name"]) ?></td>
                        <td><?= htmlspecialchars($member["email"]) ?></td>
                        <td><?= htmlspecialchars($member["phone"]) ?></td>
                        <td class="<?= htmlspecialchars($member["membership_status"]) ?>">
                            <?= htmlspecialchars(ucfirst($member["membership_status"])) ?>
                        </td>
                        <td><?= htmlspecialchars($member["created_at"]) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
