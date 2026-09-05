<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$success = "";

// Fetch existing preferences if any
$stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
$stmt->execute([$userId]);
$userPref = $stmt->fetch();

$currentGenres = $userPref ? explode(',', $userPref['preferred_genres']) : [];
$currentLangs = $userPref ? explode(',', $userPref['preferred_languages']) : [];
$currentMood = $userPref['preferred_mood'] ?? 'Exciting';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $genres = isset($_POST['genres']) ? implode(',', $_POST['genres']) : 'Action,Sci-Fi';
    $languages = isset($_POST['languages']) ? implode(',', $_POST['languages']) : 'English';
    $mood = $_POST['mood'] ?? 'Exciting';

    $saveStmt = $pdo->prepare("
        INSERT INTO user_preferences (user_id, preferred_genres, preferred_languages, preferred_mood)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            preferred_genres = VALUES(preferred_genres),
            preferred_languages = VALUES(preferred_languages),
            preferred_mood = VALUES(preferred_mood)
    ");
    $saveStmt->execute([$userId, $genres, $languages, $mood]);

    header("Location: index.php?updated=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customize Your Watch Profile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <a href="index.php" class="logo">CineMatch</a>
    <nav>
        <a href="index.php">Browse Catalog</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <div class="card-form">
        <h2>What would you like to watch?</h2>
        <p>Customize your recommendations based on your taste, language, and mood.</p>

        <form method="POST">
            <!-- 1. Genres -->
            <div class="form-group">
                <label>1. Select Your Preferred Genres (Choose 1 or more):</label>
                <div class="options-grid">
                    <?php 
                    $genresList = ['Action', 'Sci-Fi', 'Drama', 'Comedy', 'Crime', 'Thriller', 'Animation', 'Romance', 'Adventure', 'Horror', 'Mystery'];
                    foreach ($genresList as $g): 
                    ?>
                        <label class="custom-checkbox">
                            <input type="checkbox" name="genres[]" value="<?= $g ?>" <?= in_array($g, $currentGenres) ? 'checked' : '' ?>>
                            <?= $g ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 2. Languages -->
            <div class="form-group">
                <label>2. Which Languages do you watch in?</label>
                <div class="options-grid">
                    <?php 
                    $langsList = ['English', 'Hindi', 'Tamil', 'Telugu', 'Malayalam', 'Kannada', 'Korean', 'Japanese', 'Spanish', 'French'];
                    foreach ($langsList as $l): 
                    ?>
                        <label class="custom-checkbox">
                            <input type="checkbox" name="languages[]" value="<?= $l ?>" <?= in_array($l, $currentLangs) ? 'checked' : '' ?>>
                            <?= $l ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 3. Mood -->
            <div class="form-group">
                <label>3. What mood or vibe are you looking for right now?</label>
                <select name="mood" class="custom-select">
                    <?php 
                    $moods = ['Exciting' => '🔥 Adrenaline & Excitement', 'Mind-bending' => '🧠 Mind-bending / Deep plots', 'Feel-good' => '🍿 Feel-good & Relaxing', 'Heartwarming' => '❤️ Emotional & Heartwarming', 'Dark' => '🌑 Dark & Gritty', 'Inspiring' => '🏆 Inspiring & Real'];
                    foreach ($moods as $val => $label): 
                    ?>
                        <option value="<?= $val ?>" <?= ($currentMood === $val) ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn" style="width: 100%; padding: 0.8rem; font-size: 1rem;">Save Preferences & Generate Feed</button>
        </form>
    </div>
</div>

</body>
</html>