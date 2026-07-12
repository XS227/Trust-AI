-- Migration: 2026-07-12 — columns missing from trustai_clean_schema.sql,
-- discovered while restoring the trustai database from scratch after it
-- was found missing on the server entirely (docs/DB_INCIDENT_2026-07-11.md).
--
-- Method: systematically extracted every column referenced in an
-- INSERT/UPDATE statement across api/**/*.php and *.php, diffed against the
-- actual schema after the base import + all other migrations ran. These are
-- the columns that were genuinely missing — not guessed, reconstructed from
-- the exact SQL the application already runs.

-- users.user_role: NOT NULL with no default in the base schema, but no
-- application code path sets it (the login/session code uses `role`
-- instead — see 2026-05-10_vipps_login_columns.sql's note on this). Some
-- OTHER code paths (create-ambassador-application.php,
-- super-admin/create-store-with-admin.php, store-admin/create-ambassador.php)
-- DO set user_role explicitly, so the column itself is real and used — just
-- not by every INSERT. Made nullable so inserts that only set `role` don't
-- hard-fail; does not affect the paths that already set it.
ALTER TABLE users MODIFY COLUMN user_role VARCHAR(40) NULL DEFAULT NULL;

-- clicks: r.php (the live referral-click-tracking redirect — the most-hit
-- endpoint in the whole app) inserts `source` and `ip_hash`, neither of
-- which existed. `ip_address` (different column, already in the base
-- schema) is left alone — nothing found that still reads it, but no
-- evidence it's safe to drop either.
ALTER TABLE clicks
  ADD COLUMN source VARCHAR(60) DEFAULT NULL AFTER referral_code,
  ADD COLUMN ip_hash VARCHAR(64) DEFAULT NULL AFTER ip_address;

-- orders.customer_name: inserted by the order-recording flow alongside the
-- already-present customer_email; wasn't in the base schema.
ALTER TABLE orders ADD COLUMN customer_name VARCHAR(191) DEFAULT NULL AFTER platform_order_id;

-- payouts: invoice_file_path + comment inserted by the payout-request flow;
-- only invoice_url (a different, also-real field) was in the base schema.
ALTER TABLE payouts
  ADD COLUMN invoice_file_path VARCHAR(255) DEFAULT NULL AFTER invoice_url,
  ADD COLUMN comment TEXT DEFAULT NULL AFTER invoice_file_path;

-- password_resets.token_hash: api/auth/forgot-password.php carries its own
-- inline `CREATE TABLE IF NOT EXISTS password_resets (...)` with a
-- token_hash CHAR(64) column — the base schema's `password_resets` table
-- predates that refactor and only has a plain `token` column, which
-- nothing in the current codebase reads or writes. token_hash is the one
-- actually used by both forgot-password.php and reset-password.php.
ALTER TABLE password_resets
  ADD COLUMN token_hash CHAR(64) DEFAULT NULL AFTER user_id,
  ADD INDEX idx_token_hash (token_hash);
