<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PaceUp</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header>
    <div class="logo">
        <a href="/">PaceUp</a>
    </div>
    <nav>
        <ul class="nav-links">
            <li><a href="/">Home</a></li>
        </ul>
    </nav>
</header>

<main style="min-height: calc(100vh - 80px - 300px); display: flex; align-items: center;">
    <div class="form-container">
        <h2 style="text-align: center; margin-bottom: 2rem; font-weight: 800; text-transform: uppercase;">Login</h2>
        <form action="/login" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-dark" style="width: 100%; margin-top: 1rem;">Sign In</button>
            <div style="text-align: center; margin-top: 1rem; font-size: 0.9rem;">
                <p>Don't have an account? <a href="/register" style="font-weight: bold; text-decoration: underline;">Sign up</a></p>
                <p style="margin-top: 0.5rem;"><a href="/admin" style="color: #666;">Admin Login</a></p>
            </div>
        </form>
    </div>
</main>

<footer style="padding: 2rem;">
    <div class="footer-bottom" style="margin-top: 0; border-top: none;">
        &copy; 2026 PaceUp. All rights reserved.
    </div>
</footer>

</body>
</html>