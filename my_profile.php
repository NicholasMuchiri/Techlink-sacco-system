
<?php
session_start();
require_once "db.php";

// Only logged-in members can access this page.
if (!isset($_SESSION["member_id"])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "member") {
    header("Location: login.php");
    exit;
}

$memberId = (int) $_SESSION["member_id"];

try {
    $stmt = $pdo->prepare("
        SELECT full_name, email, phone, membership_status, created_at
        FROM members
        WHERE id = ?
          AND role = 'member'
    ");

    $stmt->execute([$memberId]);
    $member = $stmt->fetch();

    if (!$member) {
        http_response_code(404);
        exit("Member profile not found.");
    }
} catch (PDOException $e) {
    error_log("Unable to load member profile: " . $e->getMessage());
    http_response_code(500);
    exit("Unable to load your profile. Please try again later.");
}

function escapeProfile($value) {
    return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
}

$status = strtolower((string) $member["membership_status"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | TechLink Wealth</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px 15px;
            background: #f3f6fb;
            color: #243247;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 750px;
            margin: 0 auto;
        }

        .header {
            background: #12376a;
            color: white;
            padding: 25px;
            border-radius: 12px 12px 0 0;
        }

        .header h1 {
            margin: 0 0 8px;
        }

        .header p {
            margin: 0;
            color: #dbeafe;
        }

        .profile-card {
            background: white;
            padding: 25px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.07);
        }

        .avatar {
            width: 75px;
            height: 75px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            border-radius: 50%;
            background: #e0eaff;
            color: #12376a;
            font-size: 30px;
            font-weight: bold;
        }

        .profile-row {
            padding: 16px 0;
            border-bottom: 1px solid #e5eaf1;
        }

        .label {
            display: block;
            margin-bottom: 7px;
            color: #68778b;
            font-size: 13px;
        }

        .value {
            font-size: 16px;
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            background: #e5e7eb;
            color: #374151;
            font-size: 13px;
        }

        .status.approved {
            background: #dcfce7;
            color: #166534;
        }

        .status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 25px;
        }

        .btn {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 7px;
            background: #12376a;
            color: white;
            text-decoration: none;
        }

        .btn.secondary {
            background: #e8eef7;
            color: #12376a;
        }

        @media (max-width: 480px) {
            .profile-card {
                padding: 18px;
            }
        }
    </style>
</head>
<body>
    <main class="container">
        <header class="header">
            <h1>My Profile</h1>
            <p>TechLink Wealth Investments Limited</p>
        </header>

        <section class="profile-card">
            <div class="avatar">
                <?php
                $name = trim((string) $member["full_name"]);
                echo escapeProfile(
                    $name !== "" ? strtoupper(substr($name, 0, 1)) : "M"
                );
                ?>
            </div>

            <h2><?= escapeProfile($member["full_name"]) ?></h2>
            <p>View your registered membership information.</p>

            <div class="profile-row">
                <span class="label">Full Name</span>
                <div class="value"><?= escapeProfile($member["full_name"]) ?></div>
            </div>

            <div class="profile-row">
                <span class="label">Email Address</span>
                <div class="value"><?= escapeProfile($member["email"]) ?></div>
            </div>

            <div class="profile-row">
                <span class="label">Phone Number</span>
                <div class="value"><?= escapeProfile($member["phone"]) ?></div>
            </div>

            <div class="profile-row">
                <span class="label">Membership Status</span>
                <div class="value">
                    <span class="status <?= escapeProfile($status) ?>">
                        <?= escapeProfile(ucfirst($status)) ?>
                    </span>
                </div>
            </div>

            <div class="profile-row">
                <span class="label">Registration Date</span>
                <div class="value">
                    <?= escapeProfile(date(
                        "d M Y",
                        strtotime($member["created_at"])
                    )) ?>
                </div>
            </div>

            <div class="actions">
                <a class="btn" href="dashboard.php">Back to Dashboard</a>
                <a class="btn secondary" href="logout.php">Log Out</a>
            </div>
        </section>
    </main>
</body>
</html>
