<?php
session_start();
require_once 'config/db.php';

$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? null;

// Fetch 5 popular backdrop banners for the slider
$stmt = $pdo->query("SELECT title, poster_url FROM movies WHERE poster_url IS NOT NULL AND poster_url != '' ORDER BY imdb_rating DESC LIMIT 5");
$slides = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CineMatch - AI Movie Recommendation System</title>
    <link rel="stylesheet" href="css/style.css?v=3.0">
</head>
<body>

<!-- Header (The Top Section) -->
<header>
    <a href="home.php" class="logo">🎬 CineMatch</a>
    <nav>
        <a href="home.php" class="active">Home</a>
        <a href="#about">About</a>
        <a href="#services">Services</a>
        <a href="#contact">Contact</a>
        <a href="catalog.php">Catalog</a>
        <?php if ($userId): ?>
            <a href="preferences.php">Questionnaire</a>
            <span class="user-badge"><?= htmlspecialchars($userName) ?></span>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>

<!-- Hero Area (Above the Fold with Sliding Background) -->
<section class="hero-wrapper">
    <div class="hero-slider">
        <?php foreach ($slides as $index => $slide): ?>
            <div class="slide <?= $index === 0 ? 'active' : '' ?>" style="background-image: url('<?= htmlspecialchars($slide['poster_url']) ?>');"></div>
        <?php endforeach; ?>
    </div>
    <div class="hero-overlay"></div>

    <div class="hero-content">
        <h1 class="hero-title">Stop Scrolling. Start Watching.</h1>
        <p class="hero-subtitle">
            CineMatch solves choice paralysis by matching your instant mood, genre preferences, and language selections with official IMDb scores across 150+ cinematic titles.
        </p>
        <div class="hero-cta-group">
            <?php if ($userId): ?>
                <a href="catalog.php" class="btn" style="padding: 0.85rem 1.8rem; font-size: 0.95rem;">Explore My Dashboard</a>
                <a href="preferences.php" class="btn" style="background: #334155; padding: 0.85rem 1.8rem; font-size: 0.95rem;">Retune Taste Profile</a>
            <?php else: ?>
                <a href="register.php" class="btn" style="padding: 0.85rem 1.8rem; font-size: 0.95rem;">Get Started Free</a>
                <a href="login.php" class="btn" style="background: #334155; padding: 0.85rem 1.8rem; font-size: 0.95rem;">Log In</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Core Content (Overview of Services & Value Benefits) -->
<section class="services-section" id="services">
    <div class="section-tagline">Why CineMatch</div>
    <h2 class="section-heading-center">Intelligent Recommendations, Tailored to You</h2>

    <div class="features-grid">
        <div class="feature-box">
            <span class="feature-icon">🎯</span>
            <h3>Taste Questionnaire</h3>
            <p>Select your favorite genres, preferred regional and global languages, and specific emotional mood to calibrate a tailored feed.</p>
        </div>
        <div class="feature-box">
            <span class="feature-icon">⭐</span>
            <h3>Official IMDb Integration</h3>
            <p>Make confident viewing choices backed by verified IMDb ratings and real community reviews updated across the catalog.</p>
        </div>
        <div class="feature-box">
            <span class="feature-icon">🔄</span>
            <h3>Adaptive Feedback Loop</h3>
            <p>Every 1-to-5 star rating you leave immediately tunes your recommendation engine and eliminates movies you've already watched.</p>
        </div>
        <div class="feature-box">
            <span class="feature-icon">📑</span>
            <h3>Personalized Watchlists</h3>
            <p>Bookmark high-priority films directly to your private watchlist drawer to streamline your weekend viewing sessions.</p>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="services-section" id="about" style="border-top: 1px solid var(--border-color);">
    <div class="section-tagline">About Our Platform</div>
    <h2 class="section-heading-center">Built for True Cinema Lovers</h2>
    <p style="max-width: 750px; margin: 0 auto; text-align: center; color: var(--text-secondary); line-height: 1.8;">
        CineMatch is a hybrid recommendation system engineered using the XAMPP stack (PHP, MySQL, HTML5, CSS3). Unlike standard static streaming platforms, CineMatch analyzes multi-vector user profiles against a normalized relational catalog of 150+ international and Indian cinema releases.
    </p>
</section>

<!-- Footer (The Bottom Section) -->
<footer id="contact">
    <div class="footer-container">
        <div class="footer-col">
            <div class="logo" style="margin-bottom: 0.8rem; display: inline-block;">🎬 CineMatch</div>
            <p>Smart Movie Recommendation System designed to deliver personalized viewing suggestions in seconds.</p>
        </div>
        <div class="footer-col">
            <h4>Quick Links</h4>
            <a href="home.php">Home</a>
            <a href="#about">About</a>
            <a href="#services">Services</a>
            <a href="catalog.php">Catalog</a>
        </div>
        <div class="footer-col">
            <h4>Contact Details</h4>
            <p>Email: <a href="mailto:abc@gmail.com" style="display:inline; color:#38bdf8;">abc@gmail.com</a></p>
            <p>Help / Support: <a href="mailto:abc@gmail.com" style="display:inline; color:#38bdf8;">Gmail Assistance</a></p>
            <p>Available: 24/7 Portal Access</p>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?= date("Y") ?> CineMatch System. All Rights Reserved.
    </div>
</footer>

<!-- Background Slider Script -->
<script>
    const slides = document.querySelectorAll('.hero-slider .slide');
    let currentSlide = 0;

    function nextSlide() {
        if (slides.length <= 1) return;
        slides[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % slides.length;
        slides[currentSlide].classList.add('active');
    }

    // Auto-advance banner every 4 seconds
    setInterval(nextSlide, 4000);
</script>

</body>
</html>