session_start();
if (!isset($_SESSION['pending_user_id'])) { header('Location: login.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['pending_user_id']]);
$user = $stmt->fetch();

if ($user['otp_attempts'] >= 5 || strtotime($user['otp_expires']) < time()) {
    $error = "Code expired. Please sign in again to get a new one.";
} elseif (hash_equals($user['otp_hash'], hash('sha256', $_POST['code']))) {
    $pdo->prepare("UPDATE users SET phone_verified=1, otp_hash=NULL WHERE id=?")->execute([$user['id']]);
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    unset($_SESSION['pending_user_id']);
    header('Location: dashboard.php'); exit;
} else {
    $pdo->prepare("UPDATE users SET otp_attempts = otp_attempts + 1 WHERE id=?")->execute([$user['id']]);
    $error = "Wrong code";
}