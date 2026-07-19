<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? 'admindashboard.php' : 'customerdashboard.php'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Store — Your Next Chapter Starts Here</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="landing-body">

<nav class="landing-nav">
    <div class="landing-brand">Book Store</div>
    <div class="landing-nav-links">
        <a href="login.php" class="landing-nav-link">Log in</a>
        <a href="registration.php" class="landing-nav-btn">Sign up</a>
    </div>
</nav>

<section class="landing-hero">
    <div class="landing-spines" aria-hidden="true">
        <span style="background:#1F3864; animation-delay:0s;"></span>
        <span style="background:#B08D57; animation-delay:0.3s;"></span>
        <span style="background:#7C8B6F; animation-delay:0.6s;"></span>
        <span style="background:#A6503B; animation-delay:0.9s;"></span>
        <span style="background:#5B4E8A; animation-delay:1.2s;"></span>
        <span style="background:#1F3864; animation-delay:1.5s;"></span>
        <span style="background:#B08D57; animation-delay:1.8s;"></span>
    </div>

    <div class="landing-hero-content">
        <span class="landing-eyebrow reveal-1">Online Book Store</span>
        <h1 class="landing-title reveal-2">Your next chapter<br>starts here.</h1>
        <p class="landing-subtitle reveal-3">Browse a curated catalogue, order with a single click, and track every delivery — all in one place.</p>
        <div class="landing-cta reveal-4">
            <a href="registration.php" class="cta-btn cta-primary">Create an account</a>
            <a href="login.php" class="cta-btn cta-secondary">I already have one</a>
        </div>
    </div>
</section>

<section class="landing-features">
    <div class="feature-card scroll-reveal">
        <div class="feature-icon" style="background:#1F3864;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        </div>
        <div class="feature-title">Browse &amp; search</div>
        <div class="feature-text">Explore books by title, author, or genre with instant filtering — find exactly what you're looking for.</div>
    </div>

    <div class="feature-card scroll-reveal" style="transition-delay: 0.1s;">
        <div class="feature-icon" style="background:#B08D57;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><path d="M3 6h18"></path><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
        </div>
        <div class="feature-title">One-click orders</div>
        <div class="feature-text">Hit Buy on any book and confirm your order in seconds — no complicated checkout process.</div>
    </div>

    <div class="feature-card scroll-reveal" style="transition-delay: 0.2s;">
        <div class="feature-icon" style="background:#7C8B6F;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
        <div class="feature-title">Track your order</div>
        <div class="feature-text">Follow your order from Pending to Delivered, and keep a full history of everything you've bought.</div>
    </div>
</section>

<footer class="landing-footer">
    <p>&copy; <?= date('Y') ?> Book Store. Built for readers, by readers.</p>
</footer>

<script>
    // scroll-reveal for feature cards
    const revealEls = document.querySelectorAll('.scroll-reveal');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });
    revealEls.forEach(el => observer.observe(el));
</script>

</body>
</html>