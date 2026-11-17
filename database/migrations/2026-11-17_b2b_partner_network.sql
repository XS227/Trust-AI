-- TrustAI B2B sales/referral network (independent from Shopify order commissions).
-- Run once after backing up the production DB. MySQL 5.7+/MariaDB compatible.
-- Monetary amounts are NOK excluding VAT. No real data is seeded here.

CREATE TABLE IF NOT EXISTS b2b_programs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'NOK',
  default_commission_percent DECIMAL(5,2) NOT NULL DEFAULT 30.00,
  status ENUM('active','paused') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS b2b_partners (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  program_id INT UNSIGNED NOT NULL,
  user_id INT NOT NULL,
  parent_partner_id INT UNSIGNED NULL,
  display_name VARCHAR(160) NOT NULL,
  type ENUM('partner','seller','ambassador') NOT NULL DEFAULT 'partner',
  upline_share_percent DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  status ENUM('active','paused') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_b2b_member (program_id,user_id),
  KEY idx_b2b_parent (parent_partner_id),
  KEY idx_b2b_member_user (user_id),
  KEY idx_b2b_member_program (program_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS b2b_invites (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  program_id INT UNSIGNED NOT NULL,
  parent_partner_id INT UNSIGNED NULL,
  invited_label VARCHAR(160) NOT NULL,
  expected_vipps_email VARCHAR(255) NULL,
  expected_vipps_phone VARCHAR(32) NULL,
  member_type ENUM('partner','seller','ambassador') NOT NULL DEFAULT 'partner',
  expected_vipps_phone VARCHAR(24) NULL COMMENT 'Private server-side verification only; never committed',
  token_hash CHAR(64) NOT NULL,
  created_by_user_id INT NOT NULL,
  expires_at DATETIME NOT NULL,
  claimed_at DATETIME NULL,
  claimed_user_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_b2b_token (token_hash),
  KEY idx_b2b_invite_program (program_id, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS b2b_referrals (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  program_id INT UNSIGNED NOT NULL,
  partner_id INT UNSIGNED NOT NULL,
  org_number CHAR(9) NOT NULL,
  company_name VARCHAR(200) NOT NULL,
  brand_name VARCHAR(160) NULL,
  approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  approved_at DATETIME NULL,
  status ENUM('new','contacted','meeting','offer','won','lost') NOT NULL DEFAULT 'new',
  partner_note VARCHAR(1000) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_b2b_program_org (program_id,org_number),
  KEY idx_b2b_referrer (partner_id),
  KEY idx_b2b_referral_status (program_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS b2b_sales (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  program_id INT UNSIGNED NOT NULL,
  referral_id BIGINT UNSIGNED NOT NULL,
  invoice_reference VARCHAR(120) NOT NULL,
  revenue_ex_vat DECIMAL(13,2) NOT NULL,
  direct_cost_ex_vat DECIMAL(13,2) NOT NULL,
  commission_mode ENUM('profit_percent','fixed') NOT NULL DEFAULT 'profit_percent',
  fixed_commission DECIMAL(13,2) NULL,
  stage ENUM('draft','invoiced','paid','cancelled') NOT NULL DEFAULT 'draft',
  approved TINYINT(1) NOT NULL DEFAULT 0,
  costs_verified TINYINT(1) NOT NULL DEFAULT 0,
  updated_by_user_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_b2b_invoice (program_id,invoice_reference),
  KEY idx_b2b_sales_ref (referral_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS b2b_commissions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  sale_id BIGINT UNSIGNED NOT NULL,
  partner_id INT UNSIGNED NOT NULL,
  source_partner_id INT UNSIGNED NOT NULL,
  kind ENUM('direct','upline') NOT NULL,
  amount DECIMAL(13,2) NOT NULL,
  status ENUM('pending','ready','paid') NOT NULL DEFAULT 'pending',
  paid_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_b2b_commission (sale_id,partner_id,kind),
  KEY idx_b2b_commission_partner (partner_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS b2b_audit (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  actor_user_id INT NOT NULL,
  event_type VARCHAR(64) NOT NULL,
  object_type VARCHAR(64) NOT NULL,
  object_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_b2b_audit_object (object_type,object_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Approved first introductions are reserved for a specific invitation.
-- They are not a Vipps account or a payable commission until claimed.
CREATE TABLE IF NOT EXISTS b2b_reserved_referrals (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  program_id INT UNSIGNED NOT NULL,
  partner_label VARCHAR(160) NOT NULL,
  org_number CHAR(9) NOT NULL,
  company_name VARCHAR(200) NOT NULL,
  brand_name VARCHAR(160) NULL,
  referral_status ENUM('new','contacted','meeting','offer','won','lost') NOT NULL DEFAULT 'won',
  approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  suggested_fixed_commission DECIMAL(13,2) NULL,
  invite_id BIGINT UNSIGNED NULL,
  claimed_partner_id INT UNSIGNED NULL,
  claimed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_b2b_reserved_customer (program_id,org_number),
  UNIQUE KEY uq_b2b_reserved_invite (invite_id),
  KEY idx_b2b_reserved_claim (program_id,claimed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One-time recognition: stored server-side; no repeated popup on other devices.
CREATE TABLE IF NOT EXISTS b2b_welcome_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  program_id INT UNSIGNED NOT NULL,
  partner_id INT UNSIGNED NOT NULL,
  referral_id BIGINT UNSIGNED NOT NULL,
  event_type VARCHAR(80) NOT NULL DEFAULT 'trusted_one_first_approved',
  seen_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_b2b_welcome_once (program_id,partner_id,event_type),
  KEY idx_b2b_welcome_unseen (partner_id,seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
