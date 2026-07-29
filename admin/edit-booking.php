<?php
/**
 * admin/edit-booking.php — AJAX endpoint, POST { id, csrf, full_name, email, phone, program, preferred_dates, message, status }
 * Validates and saves edits to a booking row. Responds with JSON.
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
    respond(false, 'Invalid booking ID.', 422);
}

$allowedStatuses = ['new', 'contacted', 'confirmed', 'cancelled'];

$full_name       = trim(strip_tags($_POST['full_name']       ?? ''));
$email           = trim($_POST['email']                       ?? '');
$phone           = trim(strip_tags($_POST['phone']            ?? ''));
$program         = trim(strip_tags($_POST['program']          ?? ''));
$preferred_dates = trim(strip_tags($_POST['preferred_dates']  ?? ''));
$message         = trim(strip_tags($_POST['message']          ?? ''));
$status          = $_POST['status'] ?? '';

// Validate
$errors = [];
if (strlen($full_name) < 2)                               $errors[] = 'Name is too short.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))           $errors[] = 'Invalid email address.';
if ($program === '')                                       $errors[] = 'Program is required.';
if (!in_array($status, $allowedStatuses, true))           $errors[] = 'Invalid status.';

if (!empty($errors)) {
    respond(false, implode(' ', $errors), 422);
}

try {
    $pdo  = get_db_connection();
    $stmt = $pdo->prepare(
        'UPDATE bookings
            SET full_name = :full_name,
                email = :email,
                phone = :phone,
                program = :program,
                preferred_dates = :preferred_dates,
                message = :message,
                status = :status
          WHERE id = :id'
    );
    $stmt->execute([
        ':full_name'       => $full_name,
        ':email'           => $email,
        ':phone'           => $phone !== '' ? $phone : null,
        ':program'         => $program,
        ':preferred_dates' => $preferred_dates !== '' ? $preferred_dates : null,
        ':message'         => $message !== '' ? $message : null,
        ':status'          => $status,
        ':id'              => $id,
    ]);
    respond(true, 'Booking updated successfully.');
} catch (PDOException $e) {
    error_log('Admin edit-booking error: ' . $e->getMessage());
    respond(false, 'Something went wrong. Please try again.', 500);
}
