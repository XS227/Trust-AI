-- Vipps Login 18+ gate: persist birthDate from Vipps userinfo on the user row.
-- See database/migrations/2026-05-10_vipps_login_columns.sql for the rest of
-- the Vipps fields.
--
-- 2026-07-12: `ADD COLUMN IF NOT EXISTS` is not valid MySQL syntax (it's a
-- MariaDB extension) — this file never actually ran as committed. Fixed
-- while restoring the trustai database from scratch
-- (docs/DB_INCIDENT_2026-07-11.md).

ALTER TABLE users
  ADD COLUMN birth_date DATE DEFAULT NULL;
