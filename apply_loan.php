
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["member_id"])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "member") {
    header("Location: login.php");
    exit;
}

$memberId = (int) $_SESSION["member_id"];

$memberStmt = $pdo->prepare("
    SELECT id
    FROM members
    WHERE id = ?
      AND role = 'member'
      AND membership_status = 'approved'
");
$memberStmt->execute([$memberId]);

if (!$memberStmt->fetch()) {
    http_response_code(403);
    exit("Your membership must be approved before applying for a loan.");
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $amount = trim($_POST["amount_requested"] ?? "");
    $purpose = trim($_POST["purpose"] ?? "");
    $csrfToken = $_POST["csrf_token"] ?? "";

    if (
        !isset($_SESSION["loan_csrf_token"]) ||
        !is_string($csrfToken) ||
        !hash_equals($_SESSION["loan_csrf_token"], $csrfToken)
    ) {
        $error = "Your session verification failed. Refresh the page and try again.";
    } elseif (
        !is_string($amount) ||
        !preg_match('/^[0-9]{1,13}(\.[0-9]{1,2})?$/', $amount) ||
        (float) $amount <= 0 ||
        (float) $amount > 9999999999999.99
    ) {
        $error = "Enter a valid loan amount greater than zero.";
    } elseif (!is_string($purpose) || $purpose === "" || strlen($purpose) > 5000) {
        $error = "Enter the purpose of your loan (maximum 5,000 characters).";
    } else {
        try {
            $insert = $pdo->prepare("
                INSERT INTO loans
                    (member_id, amount_requested, purpose, loan_status)
                VALUES (?, ?, ?, 'pending')
            ");

            $insert->execute([$memberId, $amount, $purpose]);

            $_SESSION["loan_csrf_token"] = bin2hex(random_bytes(32));
            header("Location: apply_loan.php?submitted=1");
            exit;
        } catch (PDOException $e) {
            error_log("Loan application failed: " . $e->getMessage());
            $error = "We could not submit your application. Please try again.";
        }
    }
}

if (isset($_GET["submitted"])) {
    $success = "Your loan application was submitted and is awaiting admin review.";
}

if (empty($_SESSION["loan_csrf_token"])) {
    $_SESSION["loan_csrf_token"] = bin2hex(random_bytes(32));
}

function loanEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for a Loan | TechLink Wealth</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 25px 15px;
            font-family: Arial, sans-serif;
            background: #f3f6fb;
            color: #243247;
        }

        .container {
            max-width: 650px;
            margin: 0 auto;
        }

        header {
            padding: 24px;
            background: #12376a;
            color: white;
            border-radius: 12px 12px 0 0;
        }

        header h1 { margin: 0 0 8px; }
        header p { margin: 0; }

        .card {
            background: white;
            padding: 25px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 5px 18px rgba(0,0,0,.07);
        }

        label {
            display: block;
            margin: 18px 0 8px;
            font-weight: bold;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font: inherit;
        }

        textarea {
            min-height: 130px;
            resize: vertical;
        }

        button, .back {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 18px;
            border: 0;
            border-radius: 7px;
            background: #12376a;
            color: white;
            font: inherit;
            text-decoration: none;
            cursor: pointer;
        }

        .back {
            margin-left: 8px;
            background: #e8eef7;
            color: #12376a;
        }

        .message {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 7px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .note {
            color: #64748b;
            font-size: 14px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <main class="container">
        <header>
            <h1>Apply for a Loan</h1>
            <p>TechLink Wealth Investments Limited</p>
        </header>

        <section class="card">
            <p class="note">
                Submit your requested amount and explain why you need the loan.
                Submission does not guarantee approval or disbursement.
            </p>

            <?php if ($error !== ""): ?>
                <div class="message error"><?= loanEscape($error) ?></div>
            <?php endif; ?>

            <?php if ($success !== ""): ?>
                <div class="message success"><?= loanEscape($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="apply_loan.php">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= loanEscape($_SESSION["loan_csrf_token"]) ?>"
                >

                <label for="amount_requested">Loan Amount (KSh)</label>
                <input
                    type="number"
                    id="amount_requested"
                    name="amount_requested"
                    min="0.01"
                    max="9999999999999.99"
                    step="0.01"
                    required
                >

                <label for="purpose">Purpose of the Loan</label>
                <textarea
                    id="purpose"
                    name="purpose"
                    maxlength="5000"
                    required
                    placeholder="Explain how you intend to use the loan..."
                ></textarea>

                <button type="submit">Submit Application</button>
                <a class="back" href="dashboard.php">Back to Dashboard</a>
            </form>
        </section>
    </main>
</body>
</html>
