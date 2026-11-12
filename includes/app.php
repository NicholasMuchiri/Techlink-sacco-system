<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../db.php";

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
}

function money($amount): string
{
    return "KSh " . number_format((float) $amount, 2);
}

function is_logged_in(): bool
{
    return !empty($_SESSION["member_id"]) && !empty($_SESSION["role"]);
}

function redirect_workspace(): void
{
    if (($_SESSION["role"] ?? "") === "admin") {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

function require_admin(): void
{
    if (!is_logged_in() || $_SESSION["role"] !== "admin") {
        header("Location: login.php");
        exit;
    }
}

function require_member(bool $approvedOnly = false): array
{
    global $pdo;

    if (!is_logged_in() || $_SESSION["role"] !== "member") {
        header("Location: login.php");
        exit;
    }

    $sql = "SELECT id, full_name, email, phone, membership_status, created_at
            FROM members
            WHERE id = ? AND role = 'member'";

    if ($approvedOnly) {
        $sql .= " AND membership_status = 'approved'";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([(int) $_SESSION["member_id"]]);
    $member = $stmt->fetch();

    if (!$member) {
        if ($approvedOnly) {
            header("Location: dashboard.php?notice=approval");
            exit;
        }

        $_SESSION = [];
        session_destroy();
        header("Location: login.php");
        exit;
    }

    $_SESSION["member_name"] = $member["full_name"];

    return $member;
}

function csrf_token(string $key): string
{
    if (empty($_SESSION[$key]) || !is_string($_SESSION[$key])) {
        $_SESSION[$key] = bin2hex(random_bytes(32));
    }

    return $_SESSION[$key];
}

function csrf_valid(string $key, $token): bool
{
    return is_string($token)
        && isset($_SESSION[$key])
        && is_string($_SESSION[$key])
        && hash_equals($_SESSION[$key], $token);
}

function initials(string $name): string
{
    $parts = preg_split("/\s+/", trim($name)) ?: [];
    $letters = "";

    foreach ($parts as $part) {
        if ($part !== "") {
            $letters .= strtoupper(substr($part, 0, 1));
        }
        if (strlen($letters) === 2) {
            break;
        }
    }

    return $letters !== "" ? $letters : "TW";
}

function portal_open(string $title, string $active): void
{
    $role = $_SESSION["role"] ?? "member";
    $name = $_SESSION["member_name"] ?? "Account";
    $workspace = $role === "admin" ? "Staff workspace" : "Member workspace";

    $items = $role === "admin"
        ? [
            "members" => ["Members", "admin_dashboard.php"],
            "savings" => ["Savings book", "admin_savings.php"],
            "record" => ["Record deposit", "record_savings.php"],
            "transactions" => ["Transactions", "admin_transactions.php"],
            "loans" => ["Credit book", "admin_loans.php"],
        ]
        : [
            "home" => ["Overview", "dashboard.php"],
            "savings" => ["Savings", "my_savings.php"],
            "transactions" => ["Transactions", "my_transactions.php"],
            "loans" => ["My loans", "my_loans.php"],
            "apply" => ["Apply for credit", "apply_loan.php"],
            "profile" => ["Profile", "my_profile.php"],
        ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | TechLink Wealth</title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body class="app-body">
<div class="app">
    <aside class="side">
        <a class="side-brand" href="<?= $role === "admin" ? "admin_dashboard.php" : "dashboard.php" ?>">
           <span class="mark"><img src="images/logo.png" alt="" onerror="this.parentNode.textContent='TW'"></span>
            <span>
                <strong>TechLink Wealth</strong>
                <small><?= e($workspace) ?></small>
            </span>
        </a>
        <nav class="side-nav">
            <?php foreach ($items as $key => $item): ?>
                <a class="<?= $key === $active ? "is-active" : "" ?>" href="<?= e($item[1]) ?>">
                    <?= e($item[0]) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="side-user">
            <span class="avatar"><?= e(initials($name)) ?></span>
            <span>
                <strong><?= e($name) ?></strong>
                <small><?= $role === "admin" ? "Administrator" : "Member" ?></small>
            </span>
            <a class="side-out" href="logout.php">Sign out</a>
        </div>
    </aside>
    <div class="workspace">
        <header class="workspace-bar">
            <div>
                <p class="kicker"><?= e($workspace) ?></p>
                <h1><?= e($title) ?></h1>
            </div>
            <a class="text-link" href="index.php">Society home</a>
        </header>
        <main class="workspace-main">
    <?php
}

function portal_close(): void
{
    ?>
        </main>
    </div>
</div>
</body>
</html>
    <?php
}
