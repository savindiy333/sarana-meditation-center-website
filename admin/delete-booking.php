<?php
/**
 * admin/delete-booking.php — POST { id, csrf }
 * Responds with JSON.
 */
require_once __DIR__ . '/auth.php';
admin_require_login();
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

function respond(bool $success, string $message, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.', 405);
}
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf'] ?? '')) {
    respond(false, 'Session expired, please refresh the page.', 403);
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    respond(false, 'Invalid request.', 422);
}

try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('DELETE FROM bookings WHERE id = :id');
    $stmt->execute([':id' => $id]);
    respond(true, 'Booking deleted.');
} catch (PDOException $e) {
    error_log('Admin delete-booking error: ' . $e->getMessage());
    respond(false, 'Something went wrong. Please try again.', 500);
}
