
<?php
require_once "includes/app.php";

$member = require_member(true);
$memberId = (int) $member["id"];

$error = "";
$success = "";

$type = $_GET["type"] ?? "deposit";

if (!in_array($type, ["deposit", "loan_repayment"], true)) {
    $type = "deposit";
}

$token = csrf_token("deposit_csrf");

portal_open("Make a Deposit", "deposit");
?>

<style>
.deposit-layout {
    display: grid;
    grid-template-columns: 1.15fr .85fr;
    gap: 22px;
    margin-top: 22px;
}

.deposit-card {
    background: #fff;
    border-radius: 15px;
    border: 1px solid #dce5f2;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,35,85,.07);
}

.deposit-title {
    background: linear-gradient(110deg,#063675,#0875dc);
    color: white;
    padding: 21px;
    font-size: 22px;
    font-weight: 750;
}

.deposit-content {
    padding: 25px;
}

.deposit-content label {
    display: block;
    margin-bottom: 9px;
    color: #102f60;
    font-weight: 700;
}

.deposit-content input,
.deposit-content select {
    display: block;
    width: 100%;
    padding: 14px;
    margin-bottom: 20px;
    border: 1px solid #b9cce5;
    border-radius: 9px;
    background: white;
    font-size: 16px;
    box-sizing: border-box;
}

.deposit-content input:focus,
.deposit-content select:focus {
    outline: 2px solid #1682ed;
    border-color: #1682ed;
}

.deposit-banner {
    padding: 17px;
    background: #eaf3ff;
    border: 1px solid #b8d6ff;
    border-radius: 10px;
    margin-bottom: 22px;
    color: #103c75;
}

.deposit-summary {
    background: #f0f5fc;
    padding: 18px;
    border-radius: 12px;
    margin: 15px 0 22px;
}

.deposit-summary h3 {
    margin-top: 0;
}

.summary-line {
    display: flex;
    justify-content: space-between;
    margin: 12px 0;
    gap: 12px;
}

.deposit-button {
    width: 100%;
    border: none;
    border-radius: 11px;
    padding: 18px;
    background: linear-gradient(110deg,#009b48,#00bd60);
    color: white;
    font-size: 20px;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 5px 12px rgba(0,160,75,.2);
    transition: .2s;
}

.deposit-button:hover {
    background: linear-gradient(110deg,#007d39,#009e4e);
    transform: translateY(-2px);
}

.deposit-button:disabled {
    opacity: .65;
    cursor: wait;
    transform: none;
}

.deposit-message {
    padding: 15px;
    border-radius: 9px;
    margin-bottom: 18px;
}

.message-error {
    background: #ffebeb;
    color: #b91c1c;
}

.message-success {
    background: #e5f8eb;
    color: #087638;
}

.instruction-item {
    display: flex;
    gap: 13px;
    margin: 22px 0;
    align-items: flex-start;
}

.instruction-number {
    background: #0875dc;
    color: white;
    width: 30px;
    height: 30px;
    flex-shrink: 0;
    border-radius: 50%;
    display: grid;
    place-items: center;
    font-weight: bold;
}

.security-note {
    padding: 17px;
    background: #e6f8ec;
    color: #087638;
    border-radius: 10px;
    margin-top: 25px;
}

@media(max-width: 800px) {
    .deposit-layout {
        grid-template-columns: 1fr;
    }
}
</style>

<section class="panel">
    <h2>Make a Deposit</h2>
    <p class="quiet">
        Deposit directly into your Techlink Sacco account
        using M-Pesa.
    </p>
</section>

<div class="deposit-layout">

    <!-- DEPOSIT FORM -->

    <section class="deposit-card">

        <div class="deposit-title">
            📱 M-Pesa Deposit
        </div>

        <div class="deposit-content">

            <div class="deposit-banner">
                <strong>Instant M-Pesa Payment</strong>
                <p>
                    Enter your payment details below.
                    An M-Pesa payment prompt will be sent
                    to your phone when you press DEPOSIT.
                </p>
            </div>

            <div id="payment-message" aria-live="polite"></div>

            <form id="deposit-form">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= e($token) ?>">

                <div class="field">
                    <label for="transaction_type">
                        Payment purpose
                    </label>

                    <select name="transaction_type"
                            id="transaction_type"
                            required>

                        <option value="deposit"
                            <?= $type === "deposit" ? "selected" : "" ?>>
                            Savings deposit
                        </option>

                        <option value="loan_repayment"
                            <?= $type === "loan_repayment" ? "selected" : "" ?>>
                            Loan repayment
                        </option>

                    </select>
                </div>

                <div class="field">
                    <label for="amount">Amount (KSh)</label>

                    <input type="number"
                           name="amount"
                           id="amount"
                           min="1"
                           max="1000000"
                           step="1"
                           placeholder="Enter amount e.g. 1000"
                           required>
                </div>

                <div class="field">
                    <label for="phone_number">
                        M-Pesa phone number
                    </label>

                    <input type="tel"
                           name="phone_number"
                           id="phone_number"
                           placeholder="0712345678"
                           maxlength="13"
                           autocomplete="tel"
                           required>

                    <small>
                        Enter the Safaricom number that will
                        receive the payment prompt.
                    </small>
                </div>

                <div class="deposit-summary">

                    <h3>Deposit Summary</h3>

                    <div class="summary-line">
                        <span>Amount</span>
                        <strong id="summary-amount">KSh 0</strong>
                    </div>

                    <div class="summary-line">
                        <span>Purpose</span>
                        <strong id="summary-purpose">
                            Savings deposit
                        </strong>
                    </div>

                    <div class="summary-line">
                        <span>Payment method</span>
                        <strong>M-Pesa</strong>
                    </div>

                </div>

                <button type="submit"
                        class="deposit-button"
                        id="deposit-button">

                    <span>📲 DEPOSIT</span>

                </button>

            </form>

        </div>
    </section>

    <!-- PAYMENT INSTRUCTIONS -->

    <section class="deposit-card">

        <div class="deposit-title">
            ✓ How to Pay
        </div>

        <div class="deposit-content">

            <h3>Complete your deposit in six steps</h3>

            <div class="instruction-item">
                <span class="instruction-number">1</span>
                <div>
                    <strong>Select payment purpose</strong>
                    <p class="quiet">Savings or loan repayment.</p>
                </div>
            </div>

            <div class="instruction-item">
                <span class="instruction-number">2</span>
                <div>
                    <strong>Enter the amount</strong>
                    <p class="quiet">Specify how much you want to pay.</p>
                </div>
            </div>

            <div class="instruction-item">
                <span class="instruction-number">3</span>
                <div>
                    <strong>Enter your phone number</strong>
                    <p class="quiet">Use your M-Pesa registered number.</p>
                </div>
            </div>

            <div class="instruction-item">
                <span class="instruction-number">4</span>
                <div>
                    <strong>Press DEPOSIT</strong>
                    <p class="quiet">Your payment request is sent to Safaricom.</p>
                </div>
            </div>

            <div class="instruction-item">
                <span class="instruction-number">5</span>
                <div>
                    <strong>Enter your M-Pesa PIN</strong>
                    <p class="quiet">Approve the prompt on your phone.</p>
                </div>
            </div>

            <div class="instruction-item">
                <span class="instruction-number">6</span>
                <div>
                    <strong>Payment confirmation</strong>
                    <p class="quiet">Wait for Safaricom to confirm the transaction.</p>
                </div>
            </div>

            <div class="security-note">
                <strong>Security notice</strong>
                <p>
                    Never share your M-Pesa PIN with anyone.
                    Techlink Sacco will never ask you for it.
                </p>
            </div>

        </div>
    </section>

</div>

<script>
const form = document.getElementById("deposit-form");
const amount = document.getElementById("amount");
const purpose = document.getElementById("transaction_type");
const phone = document.getElementById("phone_number");
const button = document.getElementById("deposit-button");
const message = document.getElementById("payment-message");

function updateSummary() {

    const value = Number(amount.value || 0);

    document.getElementById("summary-amount").textContent =
        "KSh " + value.toLocaleString("en-KE");

    document.getElementById("summary-purpose").textContent =
        purpose.value === "deposit"
        ? "Savings deposit"
        : "Loan repayment";
}

amount.addEventListener("input", updateSummary);
purpose.addEventListener("change", updateSummary);

form.addEventListener("submit", async function(event) {

    event.preventDefault();

    message.replaceChildren();

    if (!form.reportValidity()) {
        return;
    }

    button.disabled = true;
    button.textContent = "SENDING REQUEST...";

    try {

        const response = await fetch("stk_push.php", {
            method: "POST",
            body: new FormData(form),
            headers: {
                "Accept": "application/json"
            }
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || "Unable to initiate payment."
            );
        }

        const box = document.createElement("div");
        box.className = "deposit-message message-success";

        const heading = document.createElement("strong");
        heading.textContent = "M-Pesa request sent!";

        const detail = document.createElement("p");
        detail.textContent = result.message ||
            "Check your phone and enter your M-Pesa PIN.";

        box.append(heading, detail);
        message.appendChild(box);

        button.textContent = "REQUEST SENT";

    } catch (error) {

        const box = document.createElement("div");
        box.className = "deposit-message message-error";
        box.textContent = error.message;

        message.appendChild(box);

        button.disabled = false;
        button.textContent = "📲 DEPOSIT";
    }
});
</script>

<?php portal_close(); ?>
