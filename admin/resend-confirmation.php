<?php
/**
 * admin/resend-confirmation.php — AJAX endpoint, POST { id, csrf }
 * Re-sends the visitor confirmation email for one booking, using
 * the current wording from /admin/content.php. Responds with JSON.
 */
require_once __DIR__ . '/auth.php';
admin_require_login();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../content-store.php';
require_once __DIR__ . '/../confirmation-mail.php';

use PHPMailer\PHPMailer\Exception as PHPMailerException;

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
    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        respond(false, 'Booking not found.', 404);
    }

    $contentMap = get_site_content($pdo);
    [$subject, $bodyHtml, $altBody] = render_confirmation_email(
        $contentMap,
        $booking['full_name'],
        $booking['program'],
        $booking['preferred_dates']
    );
    send_visitor_confirmation_email($booking['email'], $booking['full_name'], $subject, $bodyHtml, $altBody);

    respond(true, 'Confirmation email sent to ' . $booking['email'] . '.');
} catch (PHPMailerException $e) {
    error_log('Admin resend-confirmation mail error: ' . $e->getMessage());
    respond(false, 'Could not send the email — check the SMTP settings in config.php.', 500);
} catch (PDOException $e) {
    error_log('Admin resend-confirmation DB error: ' . $e->getMessage());
    respond(false, 'Something went wrong. Please try again.', 500);
}
