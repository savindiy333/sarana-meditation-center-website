# Saraṇa Meditation Center — Setup Guide

## What was fixed
- **Broken retreat links**: `retreats.html` had "Learn more" links pointing to a local Windows path (`C:\Users\banda\...`). These are now normal relative links (`retreat-reset-weekend.html`, etc.), so every page now links correctly to every other page.
- **The contact form was a fake demo** (`This is a demo form — no message is actually sent.`). It now really submits.

## What was added
- `database.sql` — MySQL schema (one table: `bookings`).
- `config.php` — all your settings in one place (DB + email).
- `db.php` — database connection.
- `send-booking.php` — receives the form, saves it to MySQL, and emails the appointment details.
- `vendor/phpmailer/` — the PHPMailer library (used to send mail reliably via SMTP).

## Deploy in 4 steps

1. **Upload everything** in this folder to your PHP web host (needs PHP 7.4+ and MySQL).
2. **Create the database**: in phpMyAdmin (or `mysql -u USER -p < database.sql`), import `database.sql`.
3. **Edit `config.php`**:
   - `DB_USER` / `DB_PASS` — your MySQL login.
   - `SMTP_USERNAME` / `SMTP_PASSWORD` — the Gmail (or other) account that *sends* the mail. For Gmail you must use an **App Password** (not your normal password): https://myaccount.google.com/apppasswords
   - `CLIENT_NOTIFY_EMAIL` is already set to `bandaratharani02@gmail.com` — every booking's full details land here.
4. **Test it**: open `contact.html` on your live site, submit the form, and check:
   - the `bookings` table in MySQL for the new row,
   - the inbox at `bandaratharani02@gmail.com` for the appointment-details email.

## How it works
`contact.html` → JS (`script.js`) sends the form with `fetch()` → `send-booking.php` validates it, saves it to MySQL, then emails the full appointment details (name, email, program, dates, message) to `bandaratharani02@gmail.com` via SMTP, and optionally sends the visitor a confirmation email too. On success the visitor is redirected to `thank-you.html`, same as before.

## Notes
- If your host doesn't allow outgoing SMTP on port 587, ask them which port/host to use, or use their own SMTP relay instead of Gmail.
- Set `MAIL_DEBUG` to `true` in `config.php` temporarily if email doesn't send, to see the exact SMTP error, then set it back to `false`.
- A hidden "honeypot" field silently blocks basic spam bots.
