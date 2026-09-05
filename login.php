<?php
session_start();
require_once 'config/db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];

            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid email or password combination.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - CineMatch</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <a href="index.php" class="logo">🎬 CineMatch</a>
    <nav>
        <a href="index.php">Explore</a>
        <a href="register.php">Register</a>
    </nav>
</header>

<div class="container">
    <div class="auth-box">
        <h2>Welcome Back</h2>
        <?php if ($error): ?>
            <p style="color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 0.6rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.85rem; border: 1px solid rgba(239, 68, 68, 0.2);">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="name@example.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn" style="width: 100%; padding: 0.75rem; font-size: 0.95rem;">Sign In</button>
        </form>
        <p style="text-align: center; margin-top: 1.2rem; font-size: 0.85rem; color: #94a3b8;">
            Don't have an account? <a href="register.php" style="color: #38bdf8; text-decoration: none;">Create one here</a>
        </p>
    </div>
</div>

</body>
</html>