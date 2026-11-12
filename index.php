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
           <span class="mark"><img src="images/logo.png" alt="" onerror="this.parentNode.textContent='TW'"></span>
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
            <div class="hero-slides" aria-hidden="true">
                <div class="hero-slide is-active" style="background-image: url('images/hero.jpg')"></div>
                <div class="hero-slide" style="background-image: url('images/hero-2.jpg')"></div>
                <div class="hero-slide" style="background-image: url('images/hero-3.jpg')"></div>
            </div>

            <div class="hero-content is-animating">
                <p class="kicker">TechLink Wealth Investments Limited</p>
                <h1>A society for people who build, save, and grow.</h1>
                <p class="lede">
                    Members contribute together, track every shilling, and access credit from one secure account. Built for tech professionals, students, and enthusiasts who want to grow their financial future.
                </p>
                <div class="actions">
                    <a class="btn btn-gold" href="register.php">Become a member</a>
                    <a class="btn btn-ghost" href="login.php">Member &amp; staff sign in</a>
                </div>
            </div>

            <button type="button" class="hero-next" aria-label="Next image">&rsaquo;</button>
            <div class="hero-dots"></div>
        </section>

        <section class="band">
            <article><strong>Savings</strong><span>Contributions held against your name</span></article>
            <article><strong>Credit</strong><span>Applications reviewed by the society</span></article>
            <article><strong>Records</strong><span>Deposits, receipts, and status in one book</span></article>
            <article><strong>Transactions</strong><span>Simple and transparent processes</span></article>
        </section>

        <section class="section" id="about">
            <div class="split">
                <div>
                    <p class="kicker">Who we are</p>
                    <h2>Collective capital, personal accountability.</h2>
                </div>
                <div class="about-side">
                    <p class="about-copy">
                        From students writing their first lines of code to professionals 
                        building careers in technology, TechLink Wealth Investments Limited
                         provides a trusted platform to save, grow wealth,
                          and access financial opportunities together.
                    </p>
                    <ul class="about-points">
                        <li>
                            <span>01</span>
                            <div>
                                <h3>Pool discipline</h3>
                                <p>Members contribute together and build savings through regular deposits.</p>
                            </div>
                        </li>
                        <li>
                            <span>02</span>
                            <div>
                                <h3>A clear record</h3>
                                <p>Every contribution is recorded against your membership, with dates and amounts.</p>
                            </div>
                        </li>
                        <li>
                            <span>03</span>
                            <div>
                                <h3>Credit with care</h3>
                                <p>Credit is considered only where membership and records support it.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="section" id="services">
            <p class="kicker">What members use</p>
            <h2>Why this platform?</h2>
            <div class="service-list">
                <article class="service-card">
                    <div class="service-photo" role="img" aria-label="Member savings" style="background-image: url('images/service-1.jpg')"></div>
                    <div class="service-body">
                        <h3>Empowering smart savers</h3>
                        <p>Turn disciplined saving into meaningful opportunities for tomorrow.</p>
                    </div>
                </article>
                <article class="service-card">
                    <div class="service-photo" role="img" aria-label="Deposit records" style="background-image: url('images/service-2.jpg')"></div>
                    <div class="service-body">
                        <h3>Your Growth journey.</h3>
                        <p>follow your progress with confidence and clarity in every step of the way.</p>
                    </div>
                </article>
                <article class="service-card">
                    <div class="service-photo" role="img" aria-label="Credit applications" style="background-image: url('images/service-3.jpg')"></div>
                    <div class="service-body">
                        <h3>Supporting future Tech professionals.</h3>
                        <p>Helping students and innovators achieve their ambitions through collective strength.</p>
                    </div>
                </article>
                <article class="service-card">
                    <div class="service-photo" role="img" aria-label="Staff workspace" style="background-image: url('images/service-4.jpg')"></div>
                    <div class="service-body">
                        <h3>Transparent by design.</h3>
                        <p>Managed with integrity,accountability and the interests of members at heart.</p>
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
    <script>
    (function () {
        var hero = document.getElementById("home");
        var content = hero.querySelector(".hero-content");
        var next = hero.querySelector(".hero-next");
        var dotsBox = hero.querySelector(".hero-dots");
        var slides = [].slice.call(hero.querySelectorAll(".hero-slide"));
        var dots = [], current = 0, timer = null;

        // Drop any slide whose image file is missing, so no blank slides appear.
        Promise.all(slides.map(function (slide) {
            return new Promise(function (resolve) {
                var m = /url\(["']?([^"')]+)/.exec(slide.style.backgroundImage);
                var img = new Image();
                img.onload = function () { resolve(true); };
                img.onerror = function () { resolve(false); };
                img.src = m ? m[1] : "";
            });
        })).then(function (ok) {
            slides = slides.filter(function (slide, k) {
                if (!ok[k]) { slide.remove(); }
                return ok[k];
            });
            init();
        });

        function show(n) {
            current = (n + slides.length) % slides.length;
            slides.forEach(function (s, k) { s.classList.toggle("is-active", k === current); });
            dots.forEach(function (d, k) { d.classList.toggle("is-active", k === current); });
            content.classList.remove("is-animating");
            void content.offsetWidth;               // restart the text slide-in
            content.classList.add("is-animating");
        }

        function start() {
            stop();
            if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) { return; }
            timer = setInterval(function () { show(current + 1); }, 6500);
        }
        function stop() { if (timer) { clearInterval(timer); timer = null; } }

        function init() {
            if (slides.length === 0) { next.hidden = true; dotsBox.hidden = true; return; }
            slides[0].classList.add("is-active");
            if (slides.length < 2) { next.hidden = true; dotsBox.hidden = true; return; }

            slides.forEach(function (s, k) {
                var d = document.createElement("button");
                d.type = "button";
                d.setAttribute("aria-label", "Show image " + (k + 1));
                d.addEventListener("click", function () { show(k); start(); });
                dotsBox.appendChild(d);
                dots.push(d);
            });
            dots[0].classList.add("is-active");

            next.addEventListener("click", function () { show(current + 1); start(); });
            hero.addEventListener("mouseenter", stop);
            hero.addEventListener("mouseleave", start);
            start();
        }
    })();
    </script>
    <script>
    // Scroll reveal: text and boxes slide in every time they enter the screen.
    (function () {
        if (!("IntersectionObserver" in window)) { return; }
        if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) { return; }

        var groups = [
            [".section .kicker, .section h2", "reveal-left"],
            [".about-copy", "reveal-right"],
            [".about-points li", "reveal-right"],
            [".band article", "reveal-up"],
            [".service-card", "reveal-up"],
            [".site-footer", "reveal-up"]
        ];
        var targets = [];

        groups.forEach(function (g) {
            [].slice.call(document.querySelectorAll(g[0])).forEach(function (el) {
                var siblings = [].slice.call(el.parentNode.children).filter(function (n) {
                    return n.matches(g[0]);
                });
                var delay = Math.min(siblings.indexOf(el), 4) * 110;
                el.classList.add("reveal", g[1]);
                el.style.transitionDelay = delay + "ms";
                el.dataset.revealDelay = delay;
                targets.push(el);
            });
        });

        // Runs every time an item enters or leaves the screen, scrolling down or up.
        // Shows at 15% visible, hides only when fully off-screen (avoids flicker at the edge).
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                var el = entry.target;
                if (entry.intersectionRatio >= 0.15) {
                    el.classList.add("is-visible");
                } else if (entry.intersectionRatio === 0) {
                    el.classList.remove("is-visible");
                }
            });
        }, { threshold: [0, 0.15], rootMargin: "0px 0px -6% 0px" });

        targets.forEach(function (el) { io.observe(el); });
    })();
    </script>
</body>
</html>