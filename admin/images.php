<?php
/**
 * ============================================================
 *  admin/images.php — manage the site's images
 *  Every existing photo in /images can be replaced from here —
 *  the filename stays the same, so no HTML needs to change.
 *  New images can also be uploaded for future use on the site.
 * ============================================================
 */
require_once __DIR__ . '/auth.php';
admin_require_login();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$imagesDir = realpath(__DIR__ . '/../images');
$allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$maxBytes = 8 * 1024 * 1024; // 8 MB

$message = '';
$isError = false;

function safe_filename(string $name): string {
    $name = basename($name);
    $name = preg_replace('/[^A-Za-z0-9._\-() ]/', '', $name) ?? '';
    return trim($name);
}

function validate_upload(array $file, array $allowedExt, int $maxBytes): array {
    // returns [ok, error, ext]
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [false, 'Please choose a file.', ''];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [false, 'Upload failed (error code ' . $file['error'] . ').', ''];
    }
    if ($file['size'] > $maxBytes) {
        return [false, 'File is larger than 8 MB.', ''];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        return [false, 'Only JPG, PNG, WEBP or GIF images are allowed.', ''];
    }
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return [false, 'That file doesn\'t look like a valid image.', ''];
    }
    return [true, '', $ext];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf'] ?? '')) {
        $message = 'Session expired — please try again.';
        $isError = true;
    } elseif (($_POST['action'] ?? '') === 'replace') {
        $targetName = safe_filename($_POST['target'] ?? '');
        $targetPath = $imagesDir . '/' . $targetName;
        if ($targetName === '' || !is_file($targetPath)) {
            $message = 'Unknown image.';
            $isError = true;
        } else {
            [$ok, $err] = validate_upload($_FILES['image'] ?? [], $allowedExt, $maxBytes);
            if (!$ok) {
                $message = $err;
                $isError = true;
            } else {
                // Keep the original filename/extension so every page that
                // already references it picks up the new picture automatically.
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $message = "\"{$targetName}\" was updated.";
                } else {
                    $message = 'Could not save the file — check folder permissions.';
                    $isError = true;
                }
            }
        }
    } elseif (($_POST['action'] ?? '') === 'add') {
        $newName = safe_filename($_POST['new_filename'] ?? '');
        [$ok, $err, $ext] = validate_upload($_FILES['image'] ?? [], $allowedExt, $maxBytes);
        if (!$ok) {
            $message = $err;
            $isError = true;
        } else {
            if ($newName === '') {
                $newName = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME) . '.' . $ext;
            } elseif (!preg_match('/\.[A-Za-z0-9]+$/', $newName)) {
                $newName .= '.' . $ext;
            }
            $newName = safe_filename($newName);
            $destPath = $imagesDir . '/' . $newName;
            if (is_file($destPath)) {
                $message = "\"{$newName}\" already exists — rename it or use its card above to replace it.";
                $isError = true;
            } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
                $message = "\"{$newName}\" was uploaded. Reference it as images/{$newName} in the site's HTML.";
            } else {
                $message = 'Could not save the file — check folder permissions.';
                $isError = true;
            }
        }
    }
}

// ---------- list current images ----------
$files = [];
foreach (scandir($imagesDir) ?: [] as $f) {
    if ($f === '.' || $f === '..') continue;
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) continue;
    $files[] = [
        'name' => $f,
        'size' => filesize($imagesDir . '/' . $f),
    ];
}
sort($files);
usort($files, fn($a, $b) => strcasecmp($a['name'], $b['name']));

function human_size(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / (1024 * 1024), 1) . ' MB';
}

function h($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Images — Saraṇa Admin</title>
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
      <a href="content.php">Wording</a>
      <a href="images.php" class="active">Images</a>
    </nav>
    <div class="admin-topbar-right">
      <span class="who">Signed in as <strong><?= h($_SESSION['admin_username'] ?? 'admin') ?></strong></span>
      <a href="logout.php" class="admin-btn ghost" style="width:auto;padding:8px 16px;font-size:.85rem;">Log out</a>
    </div>
  </div>

  <div class="admin-main">

    <?php if ($message): ?>
      <div class="<?= $isError ? 'admin-error' : 'admin-success' ?>"><?= h($message) ?></div>
    <?php endif; ?>

    <div class="admin-card">
      <h2>Site images</h2>
      <p class="group-note">Upload a replacement for any photo below — it keeps the same filename, so it updates instantly everywhere that image is used on the site.</p>
    </div>

    <div class="image-grid">
      <?php foreach ($files as $file): ?>
        <div class="image-card">
          <img class="thumb" src="../images/<?= h($file['name']) ?>?v=<?= filemtime($imagesDir . '/' . $file['name']) ?>" alt="<?= h($file['name']) ?>">
          <div class="image-card-body">
            <p class="filename"><?= h($file['name']) ?></p>
            <p class="filesize"><?= human_size($file['size']) ?></p>
            <form method="post" action="images.php" enctype="multipart/form-data">
              <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
              <input type="hidden" name="action" value="replace">
              <input type="hidden" name="target" value="<?= h($file['name']) ?>">
              <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" required>
              <button type="submit" class="admin-btn ghost">Replace</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="image-card admin-new-image-card">
        <div class="image-card-body">
          <p class="filename">Upload a new image</p>
          <p class="filesize">Adds a file to the images folder without replacing anything</p>
          <form method="post" action="images.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="add">
            <input type="text" name="new_filename" placeholder="Filename (optional)" class="admin-toolbar-input" style="padding:9px 12px;border-radius:var(--radius-sm);border:1px solid var(--line);font-size:.85rem;">
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" required>
            <button type="submit" class="admin-btn">Upload</button>
          </form>
        </div>
      </div>
    </div>

    <p class="admin-footer-note">Showing <?= count($files) ?> images from /images.</p>
  </div>

</body>
</html>
