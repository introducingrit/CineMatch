<?php
session_start();
require_once 'config/db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($fullName) && !empty($email) && !empty($password)) {
        // Hash password securely
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'user')");
            $stmt->execute([$fullName, $email, $hashedPassword]);

            // Auto-login upon registration
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_role'] = 'user';

            // Redirect to questionnaire to personalize feed
            header("Location: preferences.php");
            exit();
        } catch (PDOException $e) {
            $error = "This email is already registered. Please login.";
        }
    } else {
        $error = "All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - CineMatch</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <a href="index.php" class="logo">🎬 CineMatch</a>
    <nav>
        <a href="index.php">Explore</a>
        <a href="login.php">Login</a>
    </nav>
</header>

<div class="container">
    <div class="auth-box">
        <h2>Create an Account</h2>
        <?php if ($error): ?>
            <p style="color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 0.6rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.85rem; border: 1px solid rgba(239, 68, 68, 0.2);">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required placeholder="e.g. John Doe">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="name@example.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn" style="width: 100%; padding: 0.75rem; font-size: 0.95rem;">Register & Set Preferences</button>
        </form>
        <p style="text-align: center; margin-top: 1.2rem; font-size: 0.85rem; color: #94a3b8;">
            Already have an account? <a href="login.php" style="color: #38bdf8; text-decoration: none;">Login here</a>
        </p>
    </div>
</div>

</body>
</html>