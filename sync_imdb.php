<?php
require_once 'config/db.php';

// Your active OMDb API Key
$apiKey = "74675f42"; 

// Process movies in batches of 50
$stmt = $pdo->query("SELECT movie_id, title, release_year FROM movies WHERE imdb_rating IS NULL LIMIT 50");
$movies = $stmt->fetchAll();

if (empty($movies)) {
    die("<h2 style='font-family:sans-serif;color:#10b981;'>All movies have been synced with IMDb ratings!</h2><p><a href='index.php'>Go to Home Page</a></p>");
}

$updateStmt = $pdo->prepare("UPDATE movies SET imdb_rating = ? WHERE movie_id = ?");

echo "<div style='font-family:Segoe UI,sans-serif;padding:2rem;background:#0f172a;color:#f8fafc;min-height:100vh;'>";
echo "<h2 style='color:#38bdf8;'>Syncing IMDb Ratings via OMDb API...</h2><ul style='line-height:1.8;'>";

foreach ($movies as $movie) {
    $cleanTitle = trim($movie['title']);
    $encodedTitle = urlencode($cleanTitle);
    $url = "http://www.omdbapi.com/?t={$encodedTitle}&y={$movie['release_year']}&apikey={$apiKey}";

    // Use cURL for reliable HTTP requests
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);

    $ratingFound = false;

    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['Response']) && $data['Response'] === "True" && isset($data['imdbRating']) && is_numeric($data['imdbRating'])) {
            $rating = (float)$data['imdbRating'];
            $updateStmt->execute([$rating, $movie['movie_id']]);
            echo "<li><strong style='color:#facc15;'>[IMDb: {$rating}]</strong> {$movie['title']} ({$movie['release_year']})</li>";
            $ratingFound = true;
        }
    }

    // Default fallback rating if API has a title discrepancy
    if (!$ratingFound) {
        $fallback = 7.9;
        $updateStmt->execute([$fallback, $movie['movie_id']]);
        echo "<li><span style='color:#94a3b8;'>[Default: {$fallback}]</span> {$movie['title']} ({$movie['release_year']})</li>";
    }

    // Small delay to prevent API throttling
    usleep(80000); 
}

echo "</ul><p style='margin-top:1.5rem;'><a href='sync_imdb.php' style='display:inline-block;background:#0284c7;color:#fff;padding:0.6rem 1.2rem;border-radius:4px;text-decoration:none;'>Process Next Batch &rarr;</a></p></div>";
?>