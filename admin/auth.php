<?php
/**
 * ============================================================
 *  admin/auth.php
 *  Session guard — require_once this at the top of every
 *  protected admin page. Redirects to login.php if not logged in.
 * ============================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

// 30-minute inactivity timeout
const ADMIN_SESSION_TIMEOUT = 1800;

function admin_is_logged_in(): bool {
    if (empty($_SESSION['admin_logged_in'])) {
        return false;
    }
    if (isset($_SESSION['admin_last_active']) && (time() - $_SESSION['admin_last_active']) > ADMIN_SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['admin_last_active'] = time();
    return true;
}

function admin_require_login(): void {
    if (!admin_is_logged_in()) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
        header('Location: login.php?redirect=' . $redirect);
        exit;
    }
}
