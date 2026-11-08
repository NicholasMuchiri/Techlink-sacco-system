
<?php
session_start();
require_once "db.php";

// Only administrators may process membership actions.
if (
    !isset($_SESSION["member_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    http_response_code(403);
    exit("Access denied.");
}

// Accept POST requests only.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Method not allowed.");
}

// Verify CSRF token.
if (
    !isset($_POST["csrf_token"], $_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    http_response_code(403);
    exit("Invalid request token. Refresh the dashboard and try again.");
}

$memberId = filter_input(INPUT_POST, "member_id", FILTER_VALIDATE_INT);
$action = $_POST["action"] ?? "";

if (!$memberId || !in_array($action, ["approve", "reject"], true)) {
    http_response_code(400);
    exit("Invalid request.");
}

// Only pending ordinary members can be approved or rejected.
$newStatus = $action === "approve" ? "approved" : "rejected";

$stmt = $pdo->prepare(
    "UPDATE members
     SET membership_status = ?
     WHERE id = ?
       AND role = 'member'
       AND membership_status = 'pending'"
);

$stmt->execute([$newStatus, $memberId]);

header("Location: admin_dashboard.php");
exit;
