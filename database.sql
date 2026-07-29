-- ============================================================
--  Saraṇa Meditation Center — Booking / Appointment Database
--  Import this file once (phpMyAdmin > Import, or via CLI):
--    mysql -u YOUR_USER -p YOUR_DATABASE < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS sarana_meditation
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE sarana_meditation;

CREATE TABLE IF NOT EXISTS bookings (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(150)  NOT NULL,
  email         VARCHAR(190)  NOT NULL,
  phone         VARCHAR(30)   NULL,
  program       VARCHAR(150)  NOT NULL,
  preferred_dates VARCHAR(150) NULL,
  message       TEXT NULL,
  ip_address    VARCHAR(45)   NULL,
  status        ENUM('new','contacted','confirmed','cancelled') NOT NULL DEFAULT 'new',
  created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- If you already imported this file before the "phone" field existed,
-- run this once to add it to your existing table (safe to ignore any
-- "duplicate column" error — it just means it's already there):
-- ALTER TABLE bookings ADD COLUMN phone VARCHAR(30) NULL AFTER email;

-- ============================================================
--  Editable site wording (headings, taglines, confirmation
--  email text) — edited from /admin/content.php. Any key not
--  present here simply falls back to the site's original text,
--  so this table can stay empty until an admin edits something.
-- ============================================================
CREATE TABLE IF NOT EXISTS site_content (
  content_key   VARCHAR(190) NOT NULL PRIMARY KEY,
  content_value TEXT NOT NULL,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
