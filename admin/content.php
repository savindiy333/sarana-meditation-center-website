<?php
/**
 * ============================================================
 *  admin/content.php — edit the site's wording
 *  Every field here started as the site's original text
 *  (content-defaults.php). Saving writes an override into the
 *  site_content table; clearing a field back to its printed
 *  default and saving removes the override again.
 * ============================================================
 */
require_once __DIR__ . '/auth.php';
admin_require_login();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../content-store.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$defaults = require __DIR__ . '/../content-defaults.php';
$pdo = get_db_connection();

$saveMessage = '';
$saveError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf'] ?? '')) {
        $saveMessage = 'Session expired — please try again.';
        $saveError = true;
    } else {
        try {
            $upsert = $pdo->prepare(
                'INSERT INTO site_content (content_key, content_value) VALUES (:k, :v)
                 ON DUPLICATE KEY UPDATE content_value = VALUES(content_value)'
            );
            foreach ($defaults as $key => $def) {
                if (!array_key_exists($key, $_POST)) {
                    continue;
                }
                $raw = (string) $_POST[$key];
                // Allow a small set of inline tags only (matches what the templates use).
                $clean = strip_tags($raw, '<em><strong><b><i><br><p>');
                $upsert->execute([':k' => $key, ':v' => $clean]);
            }
            $saveMessage = 'Wording saved.';
        } catch (PDOException $e) {
            error_log('Admin content save error: ' . $e->getMessage());
            $saveMessage = 'Could not save — is the site_content table imported? See database.sql.';
            $saveError = true;
        }
    }
}

$current = get_site_content($pdo);

// group keys by their 'group' label, preserving definition order
$groups = [];
foreach ($defaults as $key => $def) {
    $groups[$def['group']][$key] = $def;
}

function h($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Wording — Saraṇa Admin</title>
<meta name="robots" content="noindex, nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600&family=Manrope:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="admin.css">
</head>
<body class="admin">

  <div class="admin-topbar">
    <h1>Saraṇa Admin</h1>
    <nav class="admin-nav">
      <a href="index.php">Bookings</a>
      <a href="content.php" class="active">Wording</a>
      <a href="images.php">Images</a>
    </nav>
    <div class="admin-topbar-right">
      <span class="who">Signed in as <strong><?= h($_SESSION['admin_username'] ?? 'admin') ?></strong></span>
      <a href="logout.php" class="admin-btn ghost" style="width:auto;padding:8px 16px;font-size:.85rem;">Log out</a>
    </div>
  </div>

  <div class="admin-main">

    <?php if ($saveMessage): ?>
      <div class="<?= $saveError ? 'admin-error' : 'admin-success' ?>"><?= h($saveMessage) ?></div>
    <?php endif; ?>

    <form method="post" action="content.php">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

      <?php foreach ($groups as $groupName => $keys): ?>
        <div class="admin-card">
          <h2><?= h($groupName) ?></h2>
          <?php if ($groupName === 'Booking confirmation email'): ?>
            <p class="group-note">Sent automatically when someone submits the booking form on <code>contact.html</code>. Use <code>{{name}}</code>, <code>{{program}}</code> and <code>{{dates}}</code> — they're replaced with the visitor's details.</p>
          <?php endif; ?>
          <?php foreach ($keys as $key => $def): ?>
            <div class="content-field<?= !empty($def['html']) ? ' is-html' : '' ?>">
              <label for="f_<?= h($key) ?>"><?= h($def['label']) ?></label>
              <textarea id="f_<?= h($key) ?>" name="<?= h($key) ?>"><?= h($current[$key] ?? $def['value']) ?></textarea>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>

      <div class="admin-save-bar">
        <button type="submit" class="admin-btn">Save wording</button>
        <span class="admin-save-msg">Changes appear on the live site right away.</span>
      </div>
    </form>

  </div>

</body>
</html>
