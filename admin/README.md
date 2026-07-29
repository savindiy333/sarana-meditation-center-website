# Saraṇa — Admin Panel

A small login-protected dashboard to view and manage the bookings
submitted through `contact.html`.

## Login

Go to `yoursite.com/admin/` (or `/admin/login.php`).

- **Username:** `admin`
- **Password:** `ju5Yf57erNgzVM`   ← change this immediately, see below.

This password was randomly generated when the admin panel was set
up. **Change it before you go live.**

### To set your own password

You need PHP to generate the hash (most hosts have it, or run this
on your own machine):

```
php -r "echo password_hash('yourNewPassword', PASSWORD_DEFAULT);"
```

Copy the output (starts with `$2y$...`) and paste it into
`config.php`, replacing the value of `ADMIN_PASSWORD_HASH`. You can
also change `ADMIN_USERNAME` there at the same time.

## What it does

- **`/admin/index.php`** — dashboard: stats by status (new /
  contacted / confirmed / cancelled), a search box (name, email,
  phone, program), a status filter, and a table of every booking.
  - Change a booking's status inline — saves instantly, no page
    reload.
  - Delete a booking (asks for confirmation first).
  - **Export CSV** — downloads the currently filtered list as a
    spreadsheet.
- **`/admin/login.php`** — sign in. Locks out after 5 failed
  attempts for 5 minutes (basic brute-force protection).
- **`/admin/logout.php`** — ends the session.
- Sessions auto-expire after 30 minutes of inactivity.
- All actions (status change, delete) are protected with a CSRF
  token, so they only work from the dashboard page itself.

## Requirements

Same as the rest of the site (PHP 7.4+, MySQL, `database.sql`
imported) — no extra setup needed beyond what's in the main
`README.md`, other than changing the password above.

## Security notes

- This folder should only be reachable over **HTTPS** in production
  — the login form sends the password in plain POST data, which
  HTTPS encrypts in transit.
- Consider adding your host's IP-allow-list or a second layer of
  HTTP Basic Auth in front of `/admin/` if it's ever going to hold
  more sensitive data.
- `admin/.htaccess` blocks directory listing on Apache hosts; it
  doesn't block direct access to the PHP pages (those are protected
  by the login system itself).
