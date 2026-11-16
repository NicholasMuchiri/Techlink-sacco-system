<?php
require_once "includes/app.php";
require_admin();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    $loanId = filter_input(INPUT_POST, "loan_id", FILTER_VALIDATE_INT);
    $decision = $_POST["decision"] ?? "";
    $method = $_POST["payment_method"] ?? "mpesa";
    $receipt = strtoupper(trim($_POST["mpesa_receipt"] ?? ""));
    $required = ["approved" => "pending", "rejected" => "pending", "disbursed" => "approved", "closed" => "disbursed"];

    if (!csrf_valid("loan_admin_csrf", $token)) {
        $error = "Security verification failed. Refresh and try again.";
    } elseif (!$loanId || !isset($required[$decision])) {
        $error = "Invalid loan decision.";
    } elseif (
        $decision === "disbursed" &&
        (!in_array($method, ["mpesa", "bank", "cash"], true) || ($receipt !== "" && !preg_match('/^[A-Z0-9]{6,20}$/', $receipt)))
    ) {
        $error = "Choose a payout method and enter a valid receipt reference (6-20 letters or digits), or leave it blank.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id, member_id, amount_requested, loan_status FROM loans WHERE id = ? FOR UPDATE");
            $stmt->execute([$loanId]);
            $loan = $stmt->fetch();

            if (!$loan || $loan["loan_status"] !== $required[$decision]) {
                $pdo->rollBack();
                $error = "This loan is no longer in the status that action needs.";
            } else {
                $adminId = (int) $_SESSION["member_id"];
                $waiting = false;

                if ($decision === "approved" || $decision === "rejected") {
                    $seen = $pdo->prepare("SELECT 1 FROM loan_approvals WHERE loan_id = ? AND admin_id = ?");
                    $seen->execute([$loanId, $adminId]);
                    if ($seen->fetch()) {
                        throw new RuntimeException("You have already recorded your decision on this loan.");
                    }

                    $vote = $pdo->prepare("INSERT INTO loan_approvals (loan_id, admin_id, decision) VALUES (?, ?, ?)");
                    $vote->execute([$loanId, $adminId, $decision]);

                    if ($decision === "approved") {
                        $count = $pdo->prepare("SELECT COUNT(*) FROM loan_approvals WHERE loan_id = ? AND decision = 'approved'");
                        $count->execute([$loanId]);
                        $waiting = (int) $count->fetchColumn() < 2;
                    }
                }

                if ($decision === "disbursed") {
                    $payout = $pdo->prepare(
                        "INSERT INTO transactions (member_id, transaction_type, amount, payment_method, mpesa_receipt, transaction_status)
                         VALUES (?, 'loan_disbursement', ?, ?, ?, 'confirmed')"
                    );
                    $payout->execute([
                        $loan["member_id"],
                        $loan["amount_requested"],
                        $method,
                        $receipt === "" ? null : $receipt,
                    ]);
                }

                if (!$waiting) {
                    $update = $pdo->prepare(
                        "UPDATE loans SET loan_status = ? WHERE id = ? AND loan_status = ?"
                    );
                    $update->execute([$decision, $loanId, $required[$decision]]);

                    if ($update->rowCount() !== 1) {
                        throw new RuntimeException("Loan status was not updated.");
                    }
                }

                $pdo->commit();
                $_SESSION["loan_admin_csrf"] = bin2hex(random_bytes(32));
                header("Location: admin_loans.php?updated=" . ($waiting ? "waiting" : "1"));
                exit;
            }
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Admin loan decision failed: " . $e->getMessage());
            $error = "Unable to update the application. Please try again.";
        }
    }
}

$me = (int) $_SESSION["member_id"];

try {
    $stmt = $pdo->query(
        "SELECT
            loans.id,
            members.full_name,
            members.email,
            loans.amount_requested,
            loans.purpose,
            loans.loan_status,
            loans.requested_at,
            (SELECT COALESCE(SUM(t.amount), 0) FROM transactions t
              WHERE t.member_id = loans.member_id
                AND t.transaction_type = 'loan_repayment'
                AND t.transaction_status = 'confirmed') AS repaid,
            (SELECT COUNT(*) FROM loan_approvals a WHERE a.loan_id = loans.id AND a.decision = 'approved') AS approvals,
            (SELECT a.decision FROM loan_approvals a WHERE a.loan_id = loans.id AND a.admin_id = $me) AS my_vote
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
    <p class="quiet">Each loan needs approval from both admins. Then disburse it and close it once repaid.</p>

    <?php if ($error !== ""): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET["updated"])): ?>
        <div class="alert alert-success"><?= e(($_GET["updated"] ?? "") === "waiting" ? "Your approval is recorded. The other admin must approve too." : "Loan application status updated.") ?></div>
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
                            <td class="num">
                                <?= e(money($loan["amount_requested"])) ?>
                                <?php if (in_array($status, ["disbursed", "closed"], true)): ?>
                                    <br><span class="note">Repaid by member: <?= e(money($loan["repaid"])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($loan["purpose"]) ?></td>
                            <td>
                                <span class="pill <?= e($status) ?>"><?= e($status) ?></span>
                                <?php if ($status === "pending"): ?>
                                    <br><span class="note"><?= (int) $loan["approvals"] ?>/2 approvals</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($loan["requested_at"]) ?></td>
                            <td>
                                <?php if ($status === "pending" && $loan["my_vote"] !== null): ?>
                                    <span class="note">You <?= e($loan["my_vote"]) ?> this loan. Waiting for the other admin.</span>
                                <?php elseif ($status === "pending"): ?>
                                    <form method="POST" action="admin_loans.php">
                                        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                        <input type="hidden" name="loan_id" value="<?= (int) $loan["id"] ?>">
                                        <div class="inline-actions">
                                            <button class="btn btn-ok" name="decision" value="approved" type="submit">Approve</button>
                                            <button class="btn btn-danger" name="decision" value="rejected" type="submit">Decline</button>
                                        </div>
                                    </form>
                                <?php elseif ($status === "approved"): ?>
                                    <form method="POST" action="admin_loans.php">
                                        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                        <input type="hidden" name="loan_id" value="<?= (int) $loan["id"] ?>">
                                        <div class="inline-actions">
                                            <select name="payment_method" aria-label="Payout method">
                                                <option value="mpesa">M-Pesa</option>
                                                <option value="bank">Bank</option>
                                                <option value="cash">Cash</option>
                                            </select>
                                            <input type="text" name="mpesa_receipt" maxlength="20" placeholder="Receipt (optional)" aria-label="Payout receipt">
                                            <button class="btn btn-gold" name="decision" value="disbursed" type="submit">Disburse</button>
                                        </div>
                                    </form>
                                <?php elseif ($status === "disbursed"): ?>
                                    <form method="POST" action="admin_loans.php">
                                        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                        <input type="hidden" name="loan_id" value="<?= (int) $loan["id"] ?>">
                                        <button class="btn btn-line" name="decision" value="closed" type="submit">Close loan</button>
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