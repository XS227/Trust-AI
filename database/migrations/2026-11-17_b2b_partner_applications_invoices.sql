-- Apply AFTER database/migrations/2026-11-17_b2b_partner_network.sql.
-- Does not create a person, partner, invoice or payment.
CREATE TABLE IF NOT EXISTS b2b_partner_applications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  program_id INT UNSIGNED NOT NULL,
  user_id INT NOT NULL,
  display_name VARCHAR(180) NOT NULL,
  company_name VARCHAR(200) NULL,
  note VARCHAR(1000) NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  decided_by_user_id INT NULL,
  decided_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_b2b_application (program_id,user_id),
  KEY idx_b2b_app_pending (program_id,status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Uploaded PDF bytes are ALWAYS held in a private directory outside the HTTP
-- document root, configurable with TRUSTAI_PRIVATE_INVOICE_DIR.
-- No blob, bank details, real invoice or private credentials belong in Git.
CREATE TABLE IF NOT EXISTS b2b_partner_invoices (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  program_id INT UNSIGNED NOT NULL,
  partner_id INT UNSIGNED NOT NULL,
  referral_id BIGINT UNSIGNED NOT NULL,
  commission_id BIGINT UNSIGNED NULL,
  invoice_number VARCHAR(100) NOT NULL,
  claimed_amount DECIMAL(13,2) NOT NULL,
  original_filename VARCHAR(180) NOT NULL,
  storage_filename CHAR(36) NOT NULL,
  file_size_bytes INT UNSIGNED NOT NULL,
  sha256 CHAR(64) NOT NULL,
  status ENUM('submitted','needs_revision','approved','paid') NOT NULL DEFAULT 'submitted',
  admin_note VARCHAR(1000) NULL,
  reviewed_by_user_id INT NULL,
  reviewed_at DATETIME NULL,
  paid_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_b2b_invoice_partner_number (program_id,partner_id,invoice_number),
  UNIQUE KEY uq_b2b_invoice_file (storage_filename),
  UNIQUE KEY uq_b2b_invoice_commission (commission_id),
  KEY idx_b2b_invoice_partner (partner_id,created_at),
  KEY idx_b2b_invoice_admin (program_id,status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
