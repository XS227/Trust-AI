-- Demo / test mode schema additions.
-- Adds is_demo flag to all transactional tables and onboarding status
-- columns to stores.
--
-- 2026-07-12: `ADD COLUMN IF NOT EXISTS` is not valid MySQL syntax (it's a
-- MariaDB extension) — this file never actually ran as committed. Fixed
-- while restoring the trustai database from scratch
-- (docs/DB_INCIDENT_2026-07-11.md). Note `leads` here depends on
-- 2026-07-12_leads_tables.sql having run first (that table didn't exist in
-- any prior schema file at all — found and added in the same incident).

ALTER TABLE users
  ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE stores
  ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN onboarding_status VARCHAR(40) NOT NULL DEFAULT 'pending',
  ADD COLUMN webhook_status    VARCHAR(40) NOT NULL DEFAULT 'pending',
  ADD COLUMN script_status     VARCHAR(40) NOT NULL DEFAULT 'pending',
  ADD COLUMN tracking_status   VARCHAR(40) NOT NULL DEFAULT 'pending';

ALTER TABLE ambassadors
  ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE leads
  ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE orders
  ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE clicks
  ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE payouts
  ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0;
