<?php
require_once "includes/app.php";
$loggedIn = is_logged_in();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechLink Wealth Investments Limited</title>
    <meta name="description" content="TechLink Wealth is a savings and credit society for technology professionals. Innovate. Invest. Grow.">
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <div class="topbar">Savings · Credit · Member records &nbsp;·&nbsp; Innovate. Invest. Grow.</div>

    <header class="site-header">
        <a class="brand" href="index.php">
            <span class="mark">TW</span>
            <span>
                <strong>TechLink Wealth</strong>
                <small>Savings &amp; Credit</small>
            </span>
        </a>
        <nav class="site-nav">
            <a href="#about">About</a>
            <a href="#services">Services</a>
            <?php if ($loggedIn): ?>
                <a href="logout.php">Sign out</a>
                <a class="btn btn-green" href="dashboard.php">My dashboard</a>
            <?php else: ?>
                <a href="register.php">Open an account</a>
                <a class="btn btn-green" href="login.php">Sign in</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <section class="hero" id="home">
            <div>
                <p class="kicker">TechLink Wealth Investments Limited</p>
                <h1>A society for people who build, save, and grow.</h1>
                <p class="lede">
                    Members contribute together, follow every recorded shilling,
                    and apply for credit from one secure account. Built for
                    technology professionals who want the discipline of a SACCO.
                </p>
                <div class="actions">
                    <a class="btn btn-gold" href="register.php">Become a member</a>
                    <a class="btn btn-ghost" href="login.php">Member &amp; staff sign in</a>
                </div>
            </div>

            <aside class="statement" aria-label="Illustrative member statement">
                <header>
                    <div>
                        <p>Member statement</p>
                        <strong>Illustrative account</strong>
                    </div>
                    <p>KES</p>
                </header>
                <div class="statement-row"><span>Share savings</span><b>Recorded</b></div>
                <div class="statement-row"><span>Deposits</span><b>Tracked</b></div>
                <div class="statement-row"><span>Credit</span><b>On application</b></div>
                <div class="statement-row"><span>Status</span><b>Member review</b></div>
                <footer>Sample layout only. Live balances appear after you sign in.</footer>
            </aside>
        </section>

        <section class="band">
            <article><strong>Savings</strong><span>Contributions held against your name</span></article>
            <article><strong>Credit</strong><span>Applications reviewed by the society</span></article>
            <article><strong>Records</strong><span>Deposits, receipts, and status in one book</span></article>
            <article><strong>One sign-in</strong><span>Members and staff use the same door</span></article>
        </section>

        <section class="section" id="about">
            <div class="split">
                <div>
                    <p class="kicker">Who we are</p>
                    <h2>Collective capital, personal accountability.</h2>
                </div>
                <p class="about-copy">
                    TechLink Wealth Investments Limited brings technology
                    professionals into a savings and credit society. The aim is
                    simple: pool discipline, keep a clear record of contributions,
                    and consider credit only where membership and records support it.
                </p>
            </div>
        </section>

        <section class="section" id="services">
            <p class="kicker">What members use</p>
            <h2>The services on this account.</h2>
            <div class="service-list">
                <article>
                    <span>01</span>
                    <div>
                        <h3>Member savings</h3>
                        <p>See the savings entries posted to your membership, with dates and amounts.</p>
                    </div>
                </article>
                <article>
                    <span>02</span>
                    <div>
                        <h3>Deposit records</h3>
                        <p>Follow deposits, including M-Pesa receipt numbers stored against a transaction.</p>
                    </div>
                </article>
                <article>
                    <span>03</span>
                    <div>
                        <h3>Credit applications</h3>
                        <p>Request a loan, state its purpose, and track whether it is pending, approved, or declined.</p>
                    </div>
                </article>
                <article>
                    <span>04</span>
                    <div>
                        <h3>Staff workspace</h3>
                        <p>Administrators approve membership, post eligible deposits to savings, and decide credit.</p>
                    </div>
                </article>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <strong>TechLink Wealth Investments Limited</strong>
        Innovate. Invest. Grow.
        <p class="fine">
            This website is in active development. Membership, savings, and credit
            remain subject to the society’s rules and to any registration or
            authorisation required by law. A database record is not, by itself,
            proof that funds were received.
        </p>
        <p class="fine">&copy; <?= date("Y") ?> TechLink Wealth Investments Limited</p>
    </footer>
</body>
</html>
