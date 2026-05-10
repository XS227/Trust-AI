-- Vipps Login 18+ gate: persist birthDate from Vipps userinfo on the user row.
-- See database/migrations/2026-05-10_vipps_login_columns.sql for the rest of
-- the Vipps fields. Apply with the same idempotent runner on older MySQL.

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS birth_date DATE DEFAULT NULL;
