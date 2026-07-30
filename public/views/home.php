<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PaceUp | Timeless Greatness</title>
    <!-- Use relative path assuming document root is public/ -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <!-- Icon font for search/cart (e.g. FontAwesome or basic unicode for demo) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header>
    <div class="logo">
        <a href="/">PaceUp</a>
    </div>
    <nav>
        <ul class="nav-links">
            <li><a href="#men">Men</a></li>
            <li><a href="#women">Women</a></li>
        </ul>
    </nav>
    <div class="nav-actions">
        <i class="fas fa-search" title="Search"></i>
        <i class="fas fa-shopping-cart" title="Cart"></i>
        <a href="/login" class="btn btn-dark" style="padding: 0.5rem 1rem; border-radius: 4px; font-size: 0.9rem;">Login</a>
    </div>
</header>

<main>
    <section class="hero">
        <div class="hero-content">
            <h1>Timeless Greatness</h1>
            <p>Elevate your performance with our latest collection of premium athletic gear. Built for those who demand the best.</p>
            <div class="hero-buttons">
                <button class="btn btn-primary">Shop</button>
                <button class="btn btn-secondary">Watch</button>
            </div>
        </div>
    </section>

    <section id="men">
        <h2 class="section-title">Latest Arrivals</h2>
        <div class="product-grid">
            <!-- Product Card 1 -->
            <div class="product-card">
                <img src="/assets/images/AIR+ZOOM+PEGASUS+42+WIDE.avif" alt="Air Zoom Pegasus" class="product-img">
                <div class="product-info">
                    <span class="product-title">Air Zoom Pegasus 42</span>
                    <span class="product-category">Men's Running Shoe</span>
                    <span class="product-price">$120</span>
                </div>
            </div>
            <!-- Product Card 2 -->
            <div class="product-card">
                <img src="/assets/images/NIKE+SB+DUNK+LOW+PRO.avif" alt="SB Dunk Low Pro" class="product-img">
                <div class="product-info">
                    <span class="product-title">SB Dunk Low Pro</span>
                    <span class="product-category">Skateboarding Shoe</span>
                    <span class="product-price">$110</span>
                </div>
            </div>
            <!-- Product Card 3 -->
            <div class="product-card">
                <img src="/assets/images/VAPOR+17+PRO+FG.avif" alt="Vapor 17 Pro" class="product-img">
                <div class="product-info">
                    <span class="product-title">Vapor 17 Pro FG</span>
                    <span class="product-category">Firm-Ground Soccer Cleat</span>
                    <span class="product-price">$150</span>
                </div>
            </div>
            <!-- Product Card 4 -->
            <div class="product-card">
                <img src="/assets/images/WAFFLE+RACER+SE.avif" alt="Waffle Racer SE" class="product-img">
                <div class="product-info">
                    <span class="product-title">Waffle Racer SE</span>
                    <span class="product-category">Men's Shoe</span>
                    <span class="product-price">$90</span>
                </div>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="footer-content">
        <div class="footer-col">
            <h3>PaceUp</h3>
            <p style="color: #ccc;">Inspiring athletes worldwide with timeless greatness and relentless innovation.</p>
            <div class="social-links">
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-facebook"></i></a>
            </div>
        </div>
        <div class="footer-col">
            <h3>Shop</h3>
            <ul>
                <li><a href="#">Men's</a></li>
                <li><a href="#">Women's</a></li>
                <li><a href="#">Kids</a></li>
                <li><a href="#">Sale</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h3>Support</h3>
            <ul>
                <li><a href="#">Order Status</a></li>
                <li><a href="#">Shipping & Delivery</a></li>
                <li><a href="#">Returns</a></li>
                <li><a href="#">Contact Us</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; 2026 PaceUp. All rights reserved.
    </div>
</footer>

</body>
</html>