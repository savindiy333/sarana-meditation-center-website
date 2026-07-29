<?php
/**
 * ============================================================
 *  content-store.php
 *  Merges content-defaults.php with any overrides an admin has
 *  saved in the site_content table. Always falls back to the
 *  defaults if the table is missing or the database is down —
 *  the site never breaks because of this feature.
 * ============================================================
 */
require_once __DIR__ . '/content-defaults.php';

/**
 * @return array<string,string> content_key => current text
 */
function get_site_content(PDO $pdo): array {
    $defaults = require __DIR__ . '/content-defaults.php';
    $map = [];
    foreach ($defaults as $key => $def) {
        $map[$key] = $def['value'];
    }
    try {
        $stmt = $pdo->query('SELECT content_key, content_value FROM site_content');
        foreach ($stmt as $row) {
            $map[$row['content_key']] = $row['content_value'];
        }
    } catch (\Throwable $e) {
        // Table not created yet, or DB hiccup — silently use defaults.
    }
    return $map;
}
