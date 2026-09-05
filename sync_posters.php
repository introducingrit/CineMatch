<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); // Give enough execution time

require_once 'config/db.php';

$apiKey = "74675f42"; 

// Process movies in batches of 50 that don't have an Amazon/IMDb poster yet
$stmt = $pdo->query("
    SELECT movie_id, title, release_year 
    FROM movies 
    WHERE poster_url NOT LIKE '%m.media-amazon.com%' 
       OR poster_url IS NULL
    LIMIT 50
");
$movies = $stmt->fetchAll();

if (empty($movies)) {
    die("
    <div style='background:#0f172a;color:#10b981;font-family:sans-serif;padding:3rem;text-align:center;'>
        <h2>All movies now have authentic IMDb posters!</h2>
        <p><a href='index.php' style='color:#38bdf8;font-size:1.2rem;'>Go Back to Catalog &rarr;</a></p>
    </div>");
}

$updateStmt = $pdo->prepare("UPDATE movies SET poster_url = ? WHERE movie_id = ?");

echo "<div style='font-family:Segoe UI,sans-serif;padding:2rem;background:#0f172a;color:#f8fafc;min-height:100vh;'>";
echo "<h2 style='color:#38bdf8;'>Syncing Official IMDb Posters via OMDb API...</h2><ol style='line-height:1.8;'>";

foreach ($movies as $movie) {
    $titleClean = trim($movie['title']);
    $url = "http://www.omdbapi.com/?t=" . urlencode($titleClean) . "&y=" . $movie['release_year'] . "&apikey=" . $apiKey;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    $raw = curl_exec($ch);
    curl_close($ch);

    $posterFound = false;

    if ($raw) {
        $json = json_decode($raw, true);
        if (isset($json['Response']) && $json['Response'] === 'True' && !empty($json['Poster']) && $json['Poster'] !== 'N/A') {
            $posterUrl = $json['Poster'];
            $updateStmt->execute([$posterUrl, $movie['movie_id']]);
            echo "<li><strong>{$movie['title']}</strong>: <span style='color:#10b981;'>Fetched Official IMDb Poster</span></li>";
            $posterFound = true;
        }
    }

    if (!$posterFound) {
        // Fallback to high-res dynamic card if title isn't matched
        $fallbackUrl = "https://images.weserv.nl/?url=https://dummyimage.com/400x600/151c2e/38bdf8.png&text=" . urlencode($movie['title']);
        $updateStmt->execute([$fallbackUrl, $movie['movie_id']]);
        echo "<li>{$movie['title']}: <span style='color:#94a3b8;'>Used Clean Styled Poster</span></li>";
    }

    usleep(100000); // 0.1s throttle to respect API rate limits
}

echo "</ol><p style='margin-top:2rem;'><a href='sync_posters.php' style='background:#0284c7;color:#fff;padding:0.8rem 1.6rem;border-radius:6px;text-decoration:none;font-weight:bold;'>Process Next Batch &rarr;</a></p></div>";
?>