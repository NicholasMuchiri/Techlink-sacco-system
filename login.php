
<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Login | TechLink Wealth</title>
    <link rel="stylesheet" href="login.css">
</head>

<body class="login-page">

    <header class="login-brand">
        <div class="brand-mark">TW</div>
        <h1>TechLink Wealth</h1>
        <p>Innovate. Invest. Grow.</p>
    </header>

    <main class="login-card">
        <h2>Welcome back</h2>
        <p class="login-description">
            Sign in to access your member account.
        </p>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="you@example.com"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button class="login-button" type="submit">
                Sign In
            </button>
        </form>

        <p class="register-prompt">
            New to TechLink?
            <a href="register.php">Create an account</a>
        </p>
    </main>

    <div class="back-home">
        <a href="index.php">&larr; Back to homepage</a>
    </div>

    <footer class="login-footer">
        &copy; <?php echo date("Y"); ?> TechLink Wealth Investments Limited
    </footer>

</body>
</html>
