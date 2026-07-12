-- Migration: 2026-07-12 — leads, lead_history, lead_notes tables
--
-- Found missing entirely from both trustai_clean_schema.sql and
-- trustai_full_reset.sql, despite being required by live application code
-- (api/leads/create.php, get.php, list.php, update.php, add-note.php).
-- Discovered while restoring the trustai database from scratch after it was
-- found missing on the server entirely (docs/DB_INCIDENT_2026-07-11.md) —
-- the schema files alone were not enough to make the app fully functional.
--
-- Column set reconstructed from the exact INSERT/SELECT/UPDATE statements in
-- those files, not guessed. Same conventions as the rest of
-- trustai_clean_schema.sql (INT UNSIGNED ids, DATETIME timestamps, plain KEY
-- indexes rather than FOREIGN KEY constraints — the base schema runs with
-- FOREIGN_KEY_CHECKS=0 and doesn't declare real FKs anywhere else either).

CREATE TABLE IF NOT EXISTS leads (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id INT UNSIGNED NOT NULL,
  ambassador_id INT UNSIGNED DEFAULT NULL,
  company_name VARCHAR(191) NOT NULL,
  contact_person VARCHAR(191) DEFAULT NULL,
  contact_email VARCHAR(191) DEFAULT NULL,
  contact_phone VARCHAR(60) DEFAULT NULL,
  source VARCHAR(120) DEFAULT NULL,
  offer_amount DECIMAL(14,2) DEFAULT NULL,
  commission_percent DECIMAL(5,2) DEFAULT NULL,
  commission_amount DECIMAL(14,2) DEFAULT NULL,
  follow_up_date DATE DEFAULT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'open',
  internal_notes TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_leads_store_id (store_id),
  KEY idx_leads_ambassador_id (ambassador_id),
  KEY idx_leads_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lead_history (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  lead_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED DEFAULT NULL,
  action_type VARCHAR(40) NOT NULL,
  field_name VARCHAR(60) DEFAULT NULL,
  old_value TEXT DEFAULT NULL,
  new_value TEXT DEFAULT NULL,
  comment TEXT DEFAULT NULL,
  visible_to_ambassador TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_lead_history_lead_id (lead_id),
  KEY idx_lead_history_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lead_notes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  lead_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED DEFAULT NULL,
  note_text TEXT NOT NULL,
  visible_to_ambassador TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_lead_notes_lead_id (lead_id),
  KEY idx_lead_notes_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
