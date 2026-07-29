<?php
/**
 * ============================================================
 *  Saraṇa Meditation Center — Configuration
 *  Fill in the values below for YOUR hosting/server, then
 *  leave this file as-is. Nothing else needs to be edited.
 * ============================================================
 */

// ---------- 1. MySQL DATABASE SETTINGS ----------
define('DB_HOST', 'localhost');           // usually "localhost"
define('DB_NAME', 'sarana_meditation');   // must match database.sql
define('DB_USER', 'root');                // your MySQL username
define('DB_PASS', '');                                // XAMPP default: empty password
define('DB_CHARSET', 'utf8mb4');

// ---------- 2. OUTGOING EMAIL (SMTP) SETTINGS ----------
// The form sends the appointment details to the center's inbox
// using SMTP (recommended, works reliably with Gmail) instead
// of PHP's built-in mail() function, which most hosts / Gmail
// silently reject or mark as spam.
//
// If you send FROM a Gmail account, you must create a 16-digit
// "App Password": https://myaccount.google.com/apppasswords
// (Google no longer accepts your normal Gmail password for this.)

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);                 // 587 = TLS (recommended)
define('SMTP_USERNAME', 'bandaratharani02@gmail.com');
define('SMTP_PASSWORD', 'gfxdgljouvlnyqds');
define('SMTP_ENCRYPTION', 'tls');         // 'tls' or 'ssl'

// The name shown as the sender in the email client
define('MAIL_FROM_NAME', 'Saraṇa Meditation Center');

// ---------- 3. WHO RECEIVES THE APPOINTMENT NOTIFICATION ----------
// Every time someone submits the contact/booking form, the full
// appointment details are emailed to this address.
define('CLIENT_NOTIFY_EMAIL', 'bandaratharani02@gmail.com');

// ---------- 4. SHOULD THE VISITOR ALSO GET A CONFIRMATION EMAIL? ----------
define('SEND_VISITOR_CONFIRMATION', true);

// ---------- 5. DEBUG MODE ----------
// Set to true temporarily if emails aren't sending, to see the
// full SMTP conversation in the response. Set back to false
// (0) once everything works, for security.
define('MAIL_DEBUG', false);

// ---------- 6. ADMIN PANEL LOGIN ----------
// Used to log in at /admin/login.php to view and manage bookings.
// A random password was generated for you the first time this was
// set up — CHANGE IT as soon as you log in once (see admin/README.md).
// To set your own password: run
//   php -r "echo password_hash('yourNewPassword', PASSWORD_DEFAULT);"
// and paste the result below as ADMIN_PASSWORD_HASH.
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', '$2y$10$icYWhk4acZqed4agGMlo8uZtqNTcfoSXoMFv2MEA3HJB7QeY/gmuK');
