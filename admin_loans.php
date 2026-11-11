<?php
require_once "includes/app.php";
require_admin();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    $loanId = filter_input(INPUT_POST, "loan_id", FILTER_VALIDATE_INT);
    $decision = $_POST["decision"] ?? "";

    if (!csrf_valid("loan_admin_csrf", $token)) {
        $error = "Security verification failed. Refresh and try again.";
    } elseif (!$loanId || !in_array($decision, ["approved", "rejected"], true)) {
        $error = "Invalid loan decision.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id, loan_status FROM loans WHERE id = ? FOR UPDATE");
            $stmt->execute([$loanId]);
            $loan = $stmt->fetch();

            if (!$loan || $loan["loan_status"] !== "pending") {
                $pdo->rollBack();
                $error = "This application no longer has pending status.";
            } else {
                $update = $pdo->prepare(
                    "UPDATE loans SET loan_status = ? WHERE id = ? AND loan_status = 'pending'"
                );
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
    $stmt = $pdo->query(
        "SELECT
            loans.id,
            members.full_name,
            members.email,
            loans.amount_requested,
            loans.purpose,
            loans.loan_status,
            loans.requested_at
         FROM loans
         INNER JOIN members ON members.id = loans.member_id
         ORDER BY loans.requested_at DESC"
    );
    $loans = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Unable to load loan applications: " . $e->getMessage());
    http_response_code(500);
    exit("Unable to load loan applications.");
}

$token = csrf_token("loan_admin_csrf");
portal_open("Credit book", "loans");
?>

<section class="panel">
    <h2>Loan applications</h2>
    <p class="quiet">Approve or decline requests that are still pending.</p>

    <?php if ($error !== ""): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET["updated"])): ?>
        <div class="alert alert-success">Loan application status updated.</div>
    <?php endif; ?>

    <?php if (!$loans): ?>
        <p class="empty">No loan applications have been submitted yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Member</th>
                        <th class="num">Amount</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Decision</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loans as $loan): ?>
                        <?php $status = strtolower((string) $loan["loan_status"]); ?>
                        <tr>
                            <td><?= (int) $loan["id"] ?></td>
                            <td>
                                <?= e($loan["full_name"]) ?><br>
                                <span class="note"><?= e($loan["email"]) ?></span>
                            </td>
                            <td class="num"><?= e(money($loan["amount_requested"])) ?></td>
                            <td><?= e($loan["purpose"]) ?></td>
                            <td><span class="pill <?= e($status) ?>"><?= e($status) ?></span></td>
                            <td><?= e($loan["requested_at"]) ?></td>
                            <td>
                                <?php if ($status === "pending"): ?>
                                    <form method="POST" action="admin_loans.php">
                                        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                        <input type="hidden" name="loan_id" value="<?= (int) $loan["id"] ?>">
                                        <div class="inline-actions">
                                            <button class="btn btn-ok" name="decision" value="approved" type="submit">Approve</button>
                                            <button class="btn btn-danger" name="decision" value="rejected" type="submit">Decline</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <span class="note">Recorded</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php portal_close(); ?>
