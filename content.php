<?php
/**
 * ============================================================
 *  content.php
 *  Public, read-only. Returns { key: "current text", ... } for
 *  every editable text block on the site. script.js fetches this
 *  once per page load and swaps in any admin-edited wording.
 *  No sensitive data — only the same words already on the page.
 * ============================================================
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/content-store.php';

try {
    $pdo = get_db_connection();
    echo json_encode(get_site_content($pdo));
} catch (\Throwable $e) {
    // DB unreachable — fall back to the defaults so the page still renders correctly.
    $defaults = require __DIR__ . '/content-defaults.php';
    $map = [];
    foreach ($defaults as $key => $def) {
        $map[$key] = $def['value'];
    }
    echo json_encode($map);
}
