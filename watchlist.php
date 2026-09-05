<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Handle Toggle / Remove
if (isset($_GET['action']) && isset($_GET['movie_id'])) {
    $movieId = (int)$_GET['movie_id'];
    if ($_GET['action'] === 'add') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO watchlist (user_id, movie_id) VALUES (?, ?)");
        $stmt->execute([$userId, $movieId]);
    } elseif ($_GET['action'] === 'remove') {
        $stmt = $pdo->prepare("DELETE FROM watchlist WHERE user_id = ? AND movie_id = ?");
        $stmt->execute([$userId, $movieId]);
    }
    header("Location: watchlist.php");
    exit();
}

// Fetch Watchlist
$stmt = $pdo->prepare("
    SELECT m.*, w.added_at 
    FROM watchlist w 
    JOIN movies m ON w.movie_id = m.movie_id 
    WHERE w.user_id = ? 
    ORDER BY w.added_at DESC
");
$stmt->execute([$userId]);
$watchlist = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Watchlist - CineMatch</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <a href="index.php" class="logo">🎬 CineMatch</a>
    <nav>
        <a href="index.php">Explore</a>
        <a href="preferences.php">Questionnaire</a>
        <a href="watchlist.php" class="active">Watchlist (<?= count($watchlist) ?>)</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>
<div class="container">
    <div class="section-header">
        <h2 class="section-title">Saved to My Watchlist</h2>
    </div>
    <?php if (empty($watchlist)): ?>
        <p style="color: #94a3b8;">Your watchlist is currently empty. Browse the catalog to bookmark movies!</p>
    <?php else: ?>
        <div class="movie-grid">
            <?php foreach ($watchlist as $m): ?>
                <div class="movie-card" style="padding: 0; overflow: hidden;">
                    <div class="card-poster">
                        <img src="<?= htmlspecialchars($m['poster_url']) ?>" alt="<?= htmlspecialchars($m['title']) ?>" loading="lazy" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'poster-fallback\'>🎬</div>';">
                    </div>
                    <div class="card-content">
                        <div class="card-top">
                            <div class="movie-title"><?= htmlspecialchars($m['title']) ?> (<?= $m['release_year'] ?>)</div>
                            <div class="movie-director">Directed by <?= htmlspecialchars($m['director']) ?></div>
                        </div>
                        <div class="card-bottom">
                            <a href="movie_details.php?id=<?= $m['movie_id'] ?>" class="btn">View</a>
                            <a href="watchlist.php?action=remove&movie_id=<?= $m['movie_id'] ?>" class="btn" style="background:#ef4444;">Remove</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>