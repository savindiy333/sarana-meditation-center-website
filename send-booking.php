<?php
/**
 * ============================================================
 *  send-booking.php
 *  Receives the "Visit Us" / booking form (contact.html),
 *  stores it in MySQL, and emails the full appointment
 *  details to the center (CLIENT_NOTIFY_EMAIL in config.php).
 *  Optionally sends the visitor a confirmation email too.
 *
 *  Responds with JSON: { "success": true|false, "message": "..." }
 * ============================================================
 */

header('Content-Type: application/json; charset=utf-8');

// Allow the form to be submitted with fetch() from the same site.
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/content-store.php';
require_once __DIR__ . '/confirmation-mail.php';
require_once __DIR__ . '/vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

function respond(bool $success, string $message, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.', 405);
}

// ---------- 1. Collect + sanitize input ----------
$name        = trim(strip_tags($_POST['name'] ?? ''));
$email       = trim($_POST['email'] ?? '');
$countryCode = trim(strip_tags($_POST['country_code'] ?? ''));
$phoneRaw    = trim(strip_tags($_POST['phone'] ?? ''));
$phone       = $phoneRaw !== '' ? trim($countryCode . ' ' . $phoneRaw) : '';
$program     = trim(strip_tags($_POST['program'] ?? ''));
$dates       = trim(strip_tags($_POST['dates'] ?? ''));
$message     = trim(strip_tags($_POST['message'] ?? ''));

// Honeypot spam trap (optional hidden field named "website" in the form)
if (!empty($_POST['website'] ?? '')) {
    // Bots fill hidden fields — silently pretend success, do nothing.
    respond(true, 'Message sent.');
}

// ---------- 2. Validate ----------
$errors = [];
if ($name === '' || strlen($name) < 2) {
    $errors[] = 'Please enter your full name.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}
if ($program === '') {
    $program = 'A single daily session';
}

if (!empty($errors)) {
    respond(false, implode(' ', $errors), 422);
}

// ---------- 3. Save to MySQL ----------
try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare(
        'INSERT INTO bookings (full_name, email, phone, program, preferred_dates, message, ip_address)
         VALUES (:full_name, :email, :phone, :program, :preferred_dates, :message, :ip_address)'
    );
    $stmt->execute([
        ':full_name'       => $name,
        ':email'           => $email,
        ':phone'           => $phone !== '' ? $phone : null,
        ':program'         => $program,
        ':preferred_dates' => $dates !== '' ? $dates : null,
        ':message'         => $message !== '' ? $message : null,
        ':ip_address'      => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
    $bookingId = $pdo->lastInsertId();
} catch (PDOException $e) {
    error_log('Booking DB error: ' . $e->getMessage());
    respond(false, 'Sorry, something went wrong saving your request. Please try again shortly.', 500);
}

// ---------- 4. Build the appointment-details email (to the center) ----------
$submittedAt = date('l, d F Y — g:i A') . ' (server time)';

$detailsHtml = "
<h2 style='font-family:sans-serif;color:#4b3b2a;'>New Booking / Appointment Request</h2>
<table cellpadding='8' cellspacing='0' style='font-family:sans-serif;font-size:15px;border-collapse:collapse;'>
  <tr><td><strong>Booking ID</strong></td><td>#{$bookingId}</td></tr>
  <tr><td><strong>Name</strong></td><td>" . htmlspecialchars($name) . "</td></tr>
  <tr><td><strong>Email</strong></td><td>" . htmlspecialchars($email) . "</td></tr>
  <tr><td><strong>Phone</strong></td><td>" . htmlspecialchars($phone ?: '—') . "</td></tr>
  <tr><td><strong>Interested in</strong></td><td>" . htmlspecialchars($program) . "</td></tr>
  <tr><td><strong>Preferred dates</strong></td><td>" . htmlspecialchars($dates ?: '—') . "</td></tr>
  <tr><td><strong>Message</strong></td><td>" . nl2br(htmlspecialchars($message ?: '—')) . "</td></tr>
  <tr><td><strong>Submitted</strong></td><td>{$submittedAt}</td></tr>
</table>
";

$mailErrors = [];

try {
    $mail = new PHPMailer(true);
    if (MAIL_DEBUG) {
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    }
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_ENCRYPTION === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom(SMTP_USERNAME, MAIL_FROM_NAME);
    $mail->addAddress(CLIENT_NOTIFY_EMAIL);
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = "New appointment request — {$name} ({$program})";
    $mail->Body    = $detailsHtml;
    $mail->AltBody  = "New booking #{$bookingId}\nName: {$name}\nEmail: {$email}\nPhone: {$phone}\nProgram: {$program}\nDates: {$dates}\nMessage: {$message}\nSubmitted: {$submittedAt}";

    $mail->send();
} catch (PHPMailerException $e) {
    error_log('Booking notify-email error: ' . $mail->ErrorInfo);
    $mailErrors[] = 'notify';
}

// ---------- 5. Optional: confirmation email to the visitor ----------
// Subject/body come from content-defaults.php unless an admin has
// edited them at /admin/content.php — see confirmation-mail.php.
if (SEND_VISITOR_CONFIRMATION) {
    try {
        $contentMap = get_site_content($pdo);
        [$subject, $bodyHtml, $altBody] = render_confirmation_email($contentMap, $name, $program, $dates);
        send_visitor_confirmation_email($email, $name, $subject, $bodyHtml, $altBody);
    } catch (PHPMailerException $e) {
        error_log('Visitor confirmation email error: ' . $e->getMessage());
        $mailErrors[] = 'confirmation';
    }
}

// ---------- 6. Respond ----------
if (in_array('notify', $mailErrors, true)) {
    // Booking was saved even if email failed — don't lose the lead.
    respond(true, 'Your request was saved. (Email notification had an issue — please double check SMTP settings in config.php.)');
}

respond(true, 'Thank you for submitting! We will contact you soon.');
