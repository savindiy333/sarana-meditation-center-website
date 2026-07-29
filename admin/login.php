<?php
/**
 * ============================================================
 *  admin/login.php
 * ============================================================
 */
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// already logged in? go straight to dashboard
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$redirect = $_GET['redirect'] ?? 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // basic rate limiting: 5 attempts per 5 minutes per session
    $_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? [];
    $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], fn($t) => $t > time() - 300);

    if (count($_SESSION['login_attempts']) >= 5) {
        $error = 'Too many attempts. Please wait a few minutes and try again.';
    } elseif (
        hash_equals(ADMIN_USERNAME, $username) &&
        password_verify($password, ADMIN_PASSWORD_HASH)
    ) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_last_active'] = time();
        unset($_SESSION['login_attempts']);
        header('Location: ' . ($redirect ?: 'index.php'));
        exit;
    } else {
        $_SESSION['login_attempts'][] = time();
        $error = 'Incorrect username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Saraṇa Meditation Center</title>
<meta name="robots" content="noindex, nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600&family=Manrope:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="admin.css">
</head>
<body class="admin">
  <div class="admin-login-wrap">
    <div class="admin-login-card">
      <h1>Saraṇa Admin</h1>
      <p class="sub">Sign in to view and manage booking requests.</p>

      <?php if ($error): ?>
        <div class="admin-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" action="login.php?redirect=<?= urlencode($redirect) ?>">
        <div class="admin-field">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" autocomplete="username" required autofocus>
        </div>
        <div class="admin-field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="admin-btn">Sign In</button>
      </form>
    </div>
  </div>
</body>
</html>
