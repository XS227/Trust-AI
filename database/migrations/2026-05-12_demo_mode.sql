-- Demo / test mode schema additions.
-- Adds is_demo flag to all transactional tables and onboarding status
-- columns to stores. MySQL < 8.0.29 does not support ADD COLUMN IF NOT EXISTS,
-- so use the PHP runner (api/demo/_demo_helper.php demoEnsureSchema()) which
-- checks INFORMATION_SCHEMA before each ALTER.
--
-- For MySQL 8.0.29+ / MariaDB this SQL can be run directly:

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS is_demo TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE stores
  ADD COLUMN IF NOT EXISTS is_demo TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS onboarding_status VARCHAR(40) NOT NULL DEFAULT 'pending',
  ADD COLUMN IF NOT EXISTS webhook_status    VARCHAR(40) NOT NULL DEFAULT 'pending',
  ADD COLUMN IF NOT EXISTS script_status     VARCHAR(40) NOT NULL DEFAULT 'pending',
  ADD COLUMN IF NOT EXISTS tracking_status   VARCHAR(40) NOT NULL DEFAULT 'pending';

ALTER TABLE ambassadors
  ADD COLUMN IF NOT EXISTS is_demo TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE leads
  ADD COLUMN IF NOT EXISTS is_demo TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS is_demo TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE clicks
  ADD COLUMN IF NOT EXISTS is_demo TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE payouts
  ADD COLUMN IF NOT EXISTS is_demo TINYINT(1) NOT NULL DEFAULT 0;
