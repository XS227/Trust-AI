-- Vipps Login support: provider/provider_id/vipps_sub/full_name/phone_number/role/phone columns,
-- unique index on vipps_sub, helper index on phone_number.
--
-- 2026-07-12: `ADD COLUMN/KEY IF NOT EXISTS` is not valid MySQL syntax (it's
-- a MariaDB extension, confirmed against real MySQL 8.0.46 — the "MySQL
-- 8.0.29+ supports this directly" comment that used to be here was wrong).
-- This file never actually ran as committed. Fixed while restoring the
-- trustai database from scratch (docs/DB_INCIDENT_2026-07-11.md).
--
-- Also: `phone` dropped from this ALTER — trustai_clean_schema.sql's users
-- table already has it (apparently consolidated in at some point, unlike
-- the other columns below, none of which made it into that schema file).
-- `role` here is NOT the same column as the base schema's `user_role` —
-- api/_auth.php's requireLogin() explicitly selects `role` (not
-- `user_role`), so this ADD COLUMN is load-bearing: without it every
-- authenticated request 403s. `user_role` looks like unused legacy naming
-- in the base schema; left alone, not this migration's concern.

ALTER TABLE users
  ADD COLUMN provider     VARCHAR(40)  DEFAULT NULL,
  ADD COLUMN provider_id  VARCHAR(190) DEFAULT NULL,
  ADD COLUMN vipps_sub    VARCHAR(190) DEFAULT NULL,
  ADD COLUMN full_name    VARCHAR(191) DEFAULT NULL,
  ADD COLUMN phone_number VARCHAR(60)  DEFAULT NULL,
  ADD COLUMN role         VARCHAR(40)  DEFAULT NULL;

ALTER TABLE users
  ADD UNIQUE KEY uniq_users_vipps_sub (vipps_sub),
  ADD KEY        idx_users_phone_number (phone_number);
