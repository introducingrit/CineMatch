<?php
session_start();

// If not logged in and not explicitly browsing the public catalog, redirect to home.php
if (!isset($_SESSION['user_id']) && !isset($_GET['browse'])) {
    header("Location: home.php");
    exit();
}

require_once 'config/db.php';

$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? null;

$questionnaireMatches = [];
$userPref = null;

if ($userId) {
    // 1. Fetch Questionnaire Preferences
    $prefStmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
    $prefStmt->execute([$userId]);
    $userPref = $prefStmt->fetch();

    if ($userPref) {
        $genreList = array_filter(array_map('trim', explode(',', $userPref['preferred_genres'])));
        $langList = array_filter(array_map('trim', explode(',', $userPref['preferred_languages'])));
        $preferredMood = $userPref['preferred_mood'];

        $genreClauses = [];
        $params = [];
        foreach ($genreList as $g) {
            $genreClauses[] = "m.genre LIKE ?";
            $params[] = "%$g%";
        }
        $genreSql = !empty($genreClauses) ? '(' . implode(' OR ', $genreClauses) . ')' : '1=1';

        $langIn = !empty($langList) ? implode(',', array_fill(0, count($langList), '?')) : "'English'";
        foreach ($langList as $l) {
            $params[] = $l;
        }

        $params[] = $preferredMood;
        $params[] = $userId;

        $query = "
            SELECT m.*, COALESCE(ROUND(AVG(r.rating), 1), 0) as avg_rating
            FROM movies m
            LEFT JOIN ratings r ON m.movie_id = r.movie_id
            WHERE $genreSql
              AND m.language IN ($langIn)
              AND m.mood = ?
              AND m.movie_id NOT IN (SELECT movie_id FROM ratings WHERE user_id = ?)
            GROUP BY m.movie_id
            ORDER BY avg_rating DESC, m.release_year DESC
            LIMIT 8
        ";
        $recStmt = $pdo->prepare($query);
        $recStmt->execute($params);
        $questionnaireMatches = $recStmt->fetchAll();
    }
}

// 2. All Movies Catalog
$search = $_GET['search'] ?? '';
$genreFilter = $_GET['genre'] ?? '';
$langFilter = $_GET['lang'] ?? '';

$catalogQuery = "SELECT m.*, COALESCE(ROUND(AVG(r.rating), 1), 0) as avg_rating FROM movies m LEFT JOIN ratings r ON m.movie_id = r.movie_id WHERE 1=1 ";
$catParams = [];

if ($search) {
    $catalogQuery .= " AND (m.title LIKE ? OR m.director LIKE ?)";
    $catParams[] = "%$search%";
    $catParams[] = "%$search%";
}
if ($genreFilter) {
    $catalogQuery .= " AND m.genre LIKE ?";
    $catParams[] = "%$genreFilter%";
}
if ($langFilter) {
    $catalogQuery .= " AND m.language = ?";
    $catParams[] = $langFilter;
}

$catalogQuery .= " GROUP BY m.movie_id ORDER BY m.release_year DESC LIMIT 60";
$catStmt = $pdo->prepare($catalogQuery);
$catStmt->execute($catParams);
$catalog = $catStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CineMatch - Smart Movie Recommender</title>
    <link rel="stylesheet" href="css/style.css?v=3.0">
</head>
<body>

<header>
    <a href="home.php" class="logo">🎬 CineMatch</a>
    <nav>
        <a href="home.php">Home</a>
        <a href="index.php?browse=1" class="active">Catalog</a>
        <?php if ($userId): ?>
            <a href="preferences.php">My Questionnaire</a>
            <span class="user-badge"><?= htmlspecialchars($userName) ?></span>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>

<div class="container">

    <!-- Questionnaire Banner / Dashboard Bar -->
    <?php if ($userId): ?>
        <div class="questionnaire-banner">
            <div>
                <h3>Welcome back, <?= htmlspecialchars($userName) ?>! 👋</h3>
                <p><?= $userPref ? "Preferences active: " . htmlspecialchars($userPref['preferred_mood']) . " vibe in " . htmlspecialchars($userPref['preferred_languages']) : "Take our quick questionnaire to personalize your recommendations across 150+ titles!" ?></p>
            </div>
            <a href="preferences.php" class="btn"><?= $userPref ? "Edit Questionnaire" : "Start Questionnaire" ?></a>
        </div>
    <?php else: ?>
        <div class="questionnaire-banner" style="border-color: rgba(56, 189, 248, 0.3); background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(30, 41, 59, 0.6));">
            <div>
                <h3 style="color:#38bdf8;">Unlock Tailored Movie Recommendations 🚀</h3>
                <p>Create an account to calibrate your taste profile, rate films, and maintain your watchlist.</p>
            </div>
            <div style="display:flex; gap:0.6rem;">
                <a href="login.php" class="btn">Login</a>
                <a href="register.php" class="btn" style="background:#334155;">Register Free</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Questionnaire Recommendations (Only for logged-in users who calibrated preferences) -->
    <?php if ($userId && !empty($questionnaireMatches)): ?>
        <div class="section-header">
            <h2 class="section-title">Picked For You (Matching Your Preferences)</h2>
        </div>
        <div class="movie-grid">
            <?php foreach ($questionnaireMatches as $m): ?>
                <div class="movie-card" style="padding: 0; overflow: hidden;">
                    <div class="card-poster">
                        <?php if (!empty($m['poster_url'])): ?>
                            <img src="<?= htmlspecialchars($m['poster_url']) ?>" 
                                 alt="<?= htmlspecialchars($m['title']) ?>" 
                                 loading="lazy"
                                 referrerpolicy="no-referrer"
                                 onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'poster-fallback\' style=\'padding:2rem;text-align:center;background:#151c2e;\'><div style=\'font-size:2.5rem;\'>🎬</div><div style=\'font-size:0.9rem;color:#cbd5e1;font-weight:700;margin-top:0.5rem;\'><?= addslashes(htmlspecialchars($m['title'])) ?></div></div>';">
                        <?php else: ?>
                            <div class="poster-fallback" style="padding:2rem; text-align:center; background:#151c2e;">
                                <div style="font-size:2.5rem;">🎬</div>
                                <div style="font-size:0.9rem; color:#cbd5e1; font-weight:700; margin-top:0.5rem;"><?= htmlspecialchars($m['title']) ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="poster-overlay"></div>
                    </div>

                    <div class="card-content">
                        <div class="card-top">
                            <div class="tag-row">
                                <span class="pill"><?= htmlspecialchars($m['language']) ?></span>
                                <span class="pill pill-mood"><?= htmlspecialchars($m['mood']) ?></span>
                            </div>
                            <div class="movie-title"><?= htmlspecialchars($m['title']) ?> (<?= $m['release_year'] ?>)</div>
                            <div class="movie-director">Directed by <?= htmlspecialchars($m['director']) ?></div>
                            <p class="movie-desc"><?= htmlspecialchars($m['description']) ?></p>
                        </div>

                        <div class="card-bottom">
                            <div style="display:flex; gap:0.5rem; align-items:center;">
                                <span style="background:#f5c518; color:#000; padding:2px 6px; border-radius:4px; font-weight:800; font-size:0.8rem; letter-spacing:0.5px;">
                                    IMDb <?= !empty($m['imdb_rating']) ? $m['imdb_rating'] : '8.2' ?>
                                </span>
                                <span style="font-size:0.8rem; color:#94a3b8;">
                                    <?= $m['avg_rating'] > 0 ? '★ ' . $m['avg_rating'] : '★ New' ?>
                                </span>
                            </div>
                            <a href="movie_details.php?id=<?= $m['movie_id'] ?>" class="btn">View & Rate</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Catalog Explorer Header -->
    <div class="section-header">
        <h2 class="section-title">Explore Entire Catalog (150+ Titles)</h2>
    </div>

    <!-- Filter Form -->
    <form method="GET" style="display: flex; gap: 0.8rem; margin-bottom: 2rem; flex-wrap: wrap;">
        <input type="hidden" name="browse" value="1">
        <input type="text" name="search" placeholder="Search by title or director..." value="<?= htmlspecialchars($search) ?>" class="form-input" style="max-width: 300px;">
        <select name="genre" class="custom-select" style="max-width: 180px;">
            <option value="">All Genres</option>
            <?php foreach (['Action', 'Sci-Fi', 'Drama', 'Comedy', 'Crime', 'Thriller', 'Animation', 'Romance', 'Adventure', 'Horror'] as $g): ?>
                <option value="<?= $g ?>" <?= $genreFilter === $g ? 'selected' : '' ?>><?= $g ?></option>
            <?php endforeach; ?>
        </select>
        <select name="lang" class="custom-select" style="max-width: 180px;">
            <option value="">All Languages</option>
            <?php foreach (['English', 'Hindi', 'Tamil', 'Telugu', 'Malayalam', 'Kannada', 'Korean', 'Japanese', 'Spanish', 'French'] as $l): ?>
                <option value="<?= $l ?>" <?= $langFilter === $l ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Filter</button>
        <?php if ($search || $genreFilter || $langFilter): ?>
            <a href="index.php?browse=1" class="btn" style="background: #334155;">Reset</a>
        <?php endif; ?>
    </form>

    <!-- Catalog Grid -->
    <div class="movie-grid">
        <?php foreach ($catalog as $m): ?>
            <div class="movie-card" style="padding: 0; overflow: hidden;">
                <div class="card-poster">
                    <?php if (!empty($m['poster_url'])): ?>
                        <img src="<?= htmlspecialchars($m['poster_url']) ?>" 
                             alt="<?= htmlspecialchars($m['title']) ?>" 
                             loading="lazy"
                             referrerpolicy="no-referrer"
                             onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'poster-fallback\' style=\'padding:2rem;text-align:center;background:#151c2e;\'><div style=\'font-size:2.5rem;\'>🎬</div><div style=\'font-size:0.9rem;color:#cbd5e1;font-weight:700;margin-top:0.5rem;\'><?= addslashes(htmlspecialchars($m['title'])) ?></div></div>';">
                    <?php else: ?>
                        <div class="poster-fallback" style="padding:2rem; text-align:center; background:#151c2e;">
                            <div style="font-size:2.5rem;">🎬</div>
                            <div style="font-size:0.9rem; color:#cbd5e1; font-weight:700; margin-top:0.5rem;"><?= htmlspecialchars($m['title']) ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="poster-overlay"></div>
                </div>

                <div class="card-content">
                    <div class="card-top">
                        <div class="tag-row">
                            <span class="pill"><?= htmlspecialchars($m['language']) ?></span>
                            <span class="pill pill-mood"><?= htmlspecialchars($m['mood']) ?></span>
                        </div>
                        <div class="movie-title"><?= htmlspecialchars($m['title']) ?> (<?= $m['release_year'] ?>)</div>
                        <div class="movie-director">Directed by <?= htmlspecialchars($m['director']) ?></div>
                        <p class="movie-desc"><?= htmlspecialchars($m['description']) ?></p>
                    </div>

                    <div class="card-bottom">
                        <div style="display:flex; gap:0.5rem; align-items:center;">
                            <span style="background:#f5c518; color:#000; padding:2px 6px; border-radius:4px; font-weight:800; font-size:0.8rem; letter-spacing:0.5px;">
                                IMDb <?= !empty($m['imdb_rating']) ? $m['imdb_rating'] : '8.2' ?>
                            </span>
                            <span style="font-size:0.8rem; color:#94a3b8;">
                                <?= $m['avg_rating'] > 0 ? '★ ' . $m['avg_rating'] : '★ New' ?>
                            </span>
                        </div>
                        <a href="movie_details.php?id=<?= $m['movie_id'] ?>" class="btn">View & Rate</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

</body>
</html>