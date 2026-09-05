<?php
session_start();
require_once 'config/db.php';

$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($movieId <= 0) {
    header("Location: index.php");
    exit();
}

$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? null;
$successMsg = "";

// Handle user rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    if (!$userId) {
        header("Location: login.php");
        exit();
    }
    
    $rating = (int)$_POST['rating'];
    $review = trim($_POST['review'] ?? '');

    if ($rating >= 1 && $rating <= 5) {
        $rateStmt = $pdo->prepare("
            INSERT INTO ratings (user_id, movie_id, rating, review)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                rating = VALUES(rating),
                review = VALUES(review),
                rated_at = CURRENT_TIMESTAMP
        ");
        $rateStmt->execute([$userId, $movieId, $rating, $review]);
        $successMsg = "Your rating and review have been recorded!";
    }
}

// Fetch Movie Record
$stmt = $pdo->prepare("
    SELECT m.*, COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating, COUNT(r.rating_id) AS total_reviews
    FROM movies m
    LEFT JOIN ratings r ON m.movie_id = r.movie_id
    WHERE m.movie_id = ?
    GROUP BY m.movie_id
");
$stmt->execute([$movieId]);
$movie = $stmt->fetch();

if (!$movie) {
    die("<div style='background:#0b0f19;color:#f8fafc;font-family:sans-serif;padding:3rem;text-align:center;'><h2>Movie Not Found</h2><p><a href='index.php' style='color:#38bdf8;'>Back to Catalog</a></p></div>");
}

// Fetch Existing Reviews
$revStmt = $pdo->prepare("
    SELECT r.*, u.full_name 
    FROM ratings r 
    JOIN users u ON r.user_id = u.user_id 
    WHERE r.movie_id = ? 
    ORDER BY r.rated_at DESC
");
$revStmt->execute([$movieId]);
$reviews = $revStmt->fetchAll();

// Check if current user already rated
$myRating = null;
if ($userId) {
    $myStmt = $pdo->prepare("SELECT * FROM ratings WHERE user_id = ? AND movie_id = ?");
    $myStmt->execute([$userId, $movieId]);
    $myRating = $myStmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($movie['title']) ?> - Details & Reviews</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <a href="index.php" class="logo">🎬 CineMatch</a>
    <nav>
        <a href="index.php">Explore Catalog</a>
        <?php if ($userId): ?>
            <span class="user-badge"><?= htmlspecialchars($userName) ?></span>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>

<div class="container" style="max-width: 900px;">
    
    <?php if ($successMsg): ?>
        <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #10b981; padding: 0.8rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600;">
            ✓ <?= htmlspecialchars($successMsg) ?>
        </div>
    <?php endif; ?>

    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; display: flex; flex-direction: row; gap: 1.5rem; margin-bottom: 2rem;">
        
        <div style="width: 320px; flex-shrink: 0; background: #0b0f19;">
            <img src="<?= htmlspecialchars($movie['poster_url']) ?>" 
                 alt="<?= htmlspecialchars($movie['title']) ?>" 
                 referrerpolicy="no-referrer"
                 style="width: 100%; height: 100%; object-fit: cover; min-height: 420px;"
                 onerror="this.onerror=null; this.parentElement.innerHTML='<div style=\'padding:3rem;text-align:center;font-size:3rem;\'>🎬</div>';">
        </div>

        <div style="padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between; flex-grow: 1;">
            <div>
                <div class="tag-row" style="margin-bottom: 0.8rem;">
                    <span class="pill"><?= htmlspecialchars($movie['language']) ?></span>
                    <span class="pill pill-mood"><?= htmlspecialchars($movie['mood']) ?></span>
                </div>

                <h1 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 0.3rem;"><?= htmlspecialchars($movie['title']) ?> (<?= $movie['release_year'] ?>)</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem;">
                    Directed by <strong style="color: var(--text-primary);"><?= htmlspecialchars($movie['director']) ?></strong> | Genre: <?= htmlspecialchars($movie['genre']) ?>
                </p>

                <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1.5rem;">
                    <span style="background: #f5c518; color: #000; padding: 4px 8px; border-radius: 4px; font-weight: 800; font-size: 0.9rem;">
                        IMDb <?= !empty($movie['imdb_rating']) ? $movie['imdb_rating'] : '8.0' ?>
                    </span>
                    <span style="color: #fbbf24; font-weight: 700; font-size: 0.95rem;">
                        ★ <?= $movie['avg_rating'] > 0 ? $movie['avg_rating'] . ' / 5' : 'Unrated yet' ?>
                    </span>
                    <span style="color: var(--text-secondary); font-size: 0.85rem;">
                        (<?= $movie['total_reviews'] ?> audience reviews)
                    </span>
                </div>

                <p style="color: #cbd5e1; line-height: 1.6; font-size: 0.95rem;">
                    <?= htmlspecialchars($movie['description']) ?>
                </p>
            </div>

            <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                <a href="index.php" class="btn" style="background: #334155;">&larr; Back to Catalog</a>
            </div>
        </div>
    </div>

    <!-- Rating Submission Card -->
    <div class="card-form" style="max-width: 100%; margin-bottom: 2rem;">
        <h2 style="font-size: 1.3rem;">Rate this Title</h2>
        <p>Your rating directly tunes your personalized recommendation feed.</p>

        <?php if ($userId): ?>
            <form method="POST">
                <div class="form-group">
                    <label>Select Rating (1 to 5 Stars):</label>
                    <select name="rating" class="custom-select" style="max-width: 200px;" required>
                        <option value="5" <?= ($myRating && $myRating['rating'] == 5) ? 'selected' : '' ?>>★★★★★ (5 - Masterpiece)</option>
                        <option value="4" <?= ($myRating && $myRating['rating'] == 4) ? 'selected' : '' ?>>★★★★☆ (4 - Great)</option>
                        <option value="3" <?= ($myRating && $myRating['rating'] == 3) ? 'selected' : '' ?>>★★★☆☆ (3 - Good)</option>
                        <option value="2" <?= ($myRating && $myRating['rating'] == 2) ? 'selected' : '' ?>>★★☆☆☆ (2 - Mediocre)</option>
                        <option value="1" <?= ($myRating && $myRating['rating'] == 1) ? 'selected' : '' ?>>★☆☆☆☆ (1 - Poor)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Your Review / Thoughts (Optional):</label>
                    <textarea name="review" rows="3" class="form-input" placeholder="What stood out to you about the story, performances, or pacing?"><?= htmlspecialchars($myRating['review'] ?? '') ?></textarea>
                </div>

                <button type="submit" name="submit_rating" class="btn">
                    <?= $myRating ? 'Update My Rating' : 'Submit Review' ?>
                </button>
            </form>
        <?php else: ?>
            <p style="color: #94a3b8;">
                Please <a href="login.php" style="color: #38bdf8; text-decoration: underline;">Log in</a> or <a href="register.php" style="color: #38bdf8; text-decoration: underline;">Register</a> to rate this movie.
            </p>
        <?php endif; ?>
    </div>

    <!-- Community Reviews Section -->
    <div class="section-header">
        <h2 class="section-title">Audience Feedback (<?= count($reviews) ?>)</h2>
    </div>

    <?php if (empty($reviews)): ?>
        <p style="color: var(--text-secondary); margin-bottom: 3rem;">No community reviews submitted yet for this title.</p>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 3rem;">
            <?php foreach ($reviews as $r): ?>
                <div style="background: var(--bg-card); border: 1px solid var(--border-color); padding: 1.2rem; border-radius: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                        <strong style="color: var(--text-primary); font-size: 0.95rem;"><?= htmlspecialchars($r['full_name']) ?></strong>
                        <span style="color: #fbbf24; font-size: 0.9rem;">
                            <?= str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) ?>
                        </span>
                    </div>
                    <p style="color: #cbd5e1; font-size: 0.88rem; line-height: 1.5;">
                        <?= htmlspecialchars($r['review'] ?: 'Rated ' . $r['rating'] . ' / 5 stars.') ?>
                    </p>
                    <span style="color: #64748b; font-size: 0.75rem; margin-top: 0.5rem; display: block;">
                        <?= date("F j, Y", strtotime($r['rated_at'])) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>