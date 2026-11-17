
<?php

// Configure secure session handling before starting the session.
if (session_status() === PHP_SESSION_NONE) {
    ini_set("session.use_only_cookies", "1");
    ini_set("session.use_strict_mode", "1");

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}

// Load the existing database connection.
require_once __DIR__ . "/../db.php";

// Check whether someone is logged in.
function is_logged_in(): bool
{
    return isset($_SESSION["member_id"]);
}

// Redirect users to their respective dashboards.
function redirect_workspace(): void
{
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }

    if (($_SESSION["role"] ?? "") === "admin") {
        header("Location: admin_dashboard.php");
        exit;
    }

    header("Location: dashboard.php");
    exit;
}

// Safely display text in HTML.
function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}
