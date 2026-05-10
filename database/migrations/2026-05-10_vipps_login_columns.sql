-- Vipps Login support: provider/provider_id/vipps_sub/full_name/phone_number/role/phone columns,
-- unique index on vipps_sub, helper index on phone_number.
--
-- This file documents the intended schema. Older MySQL versions (< 8.0.29)
-- do not support `ADD COLUMN IF NOT EXISTS`, so for live application use the
-- idempotent runner in /api/_vipps_migrate.php (delete after use), which
-- checks INFORMATION_SCHEMA per column/index before applying.
--
-- For MySQL 8.0.29+ / MariaDB this SQL can be run directly:

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS provider     VARCHAR(40)  DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS provider_id  VARCHAR(190) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS vipps_sub    VARCHAR(190) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS full_name    VARCHAR(191) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS phone_number VARCHAR(60)  DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS role         VARCHAR(40)  DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS phone        VARCHAR(60)  DEFAULT NULL;

ALTER TABLE users
  ADD UNIQUE KEY IF NOT EXISTS uniq_users_vipps_sub (vipps_sub),
  ADD KEY        IF NOT EXISTS idx_users_phone_number (phone_number);
