-- TrustAI clean production schema
-- Import in phpMyAdmin: open this file and run it as one SQL script.
-- WARNING: this script drops and recreates tables, resetting existing data.
-- After import, login with admin@trustai.no / TrustAiAdmin!2026.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS clicks;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS referrals;
DROP TABLE IF EXISTS payouts;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS ambassadors;
DROP TABLE IF EXISTS stores;
DROP TABLE IF EXISTS admin_users;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(191) NOT NULL,
  store_id INT UNSIGNED DEFAULT NULL,
  ambassador_id INT UNSIGNED DEFAULT NULL,
  phone VARCHAR(60) DEFAULT NULL,
  name VARCHAR(191) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  user_role VARCHAR(40) NOT NULL,
  user_type VARCHAR(40) NOT NULL DEFAULT 'internal',
  status VARCHAR(40) NOT NULL DEFAULT 'active',
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_users_email (email),
  KEY idx_users_user_role (user_role),
  KEY idx_users_store_id (store_id),
  KEY idx_users_ambassador_id (ambassador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED DEFAULT NULL,
  ambassador_id INT UNSIGNED DEFAULT NULL,
  email VARCHAR(191) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(40) NOT NULL DEFAULT 'admin',
  status VARCHAR(40) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_admin_users_email (email),
  KEY idx_admin_users_user_id (user_id),
  KEY idx_admin_users_ambassador_id (ambassador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stores (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(191) NOT NULL,
  url VARCHAR(255) DEFAULT NULL,
  domain VARCHAR(191) NOT NULL,
  platform VARCHAR(60) NOT NULL DEFAULT 'Shopify',
  public_url VARCHAR(255) DEFAULT NULL,
  contact_name VARCHAR(191) DEFAULT NULL,
  contact_title VARCHAR(191) DEFAULT NULL,
  contact_email VARCHAR(191) DEFAULT NULL,
  contact_phone VARCHAR(60) DEFAULT NULL,
  default_commission_percent DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  commission_percent DECIMAL(6,2) DEFAULT NULL,
  commission_amount DECIMAL(12,2) DEFAULT NULL,
  owner_user_id INT UNSIGNED DEFAULT NULL,
  store_admin_user_id INT UNSIGNED DEFAULT NULL,
  shopify_domain VARCHAR(191) DEFAULT NULL,
  shopify_shop VARCHAR(191) DEFAULT NULL,
  access_token VARCHAR(255) DEFAULT NULL,
  webhook_secret VARCHAR(255) DEFAULT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_stores_domain (domain),
  KEY idx_stores_owner_user_id (owner_user_id),
  KEY idx_stores_store_admin_user_id (store_admin_user_id),
  KEY idx_stores_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ambassadors (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED DEFAULT NULL,
  store_id INT UNSIGNED NOT NULL,
  name VARCHAR(191) DEFAULT NULL,
  ambassador_name VARCHAR(191) DEFAULT NULL,
  email VARCHAR(191) NOT NULL,
  phone VARCHAR(60) DEFAULT NULL,
  code VARCHAR(120) DEFAULT NULL,
  referral_code VARCHAR(120) NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'pending',
  approved_at DATETIME DEFAULT NULL,
  rejected_at DATETIME DEFAULT NULL,
  commission_percent DECIMAL(6,2) DEFAULT NULL,
  commission_amount DECIMAL(12,2) DEFAULT NULL,
  total_revenue DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  sales_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_ambassadors_referral_code (referral_code),
  KEY idx_ambassadors_store_id (store_id),
  KEY idx_ambassadors_user_id (user_id),
  KEY idx_ambassadors_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id INT UNSIGNED NOT NULL,
  ambassador_id INT UNSIGNED DEFAULT NULL,
  ambassador_code VARCHAR(120) DEFAULT NULL,
  referral_code VARCHAR(120) DEFAULT NULL,
  order_id VARCHAR(120) DEFAULT NULL,
  order_name VARCHAR(191) DEFAULT NULL,
  email VARCHAR(191) DEFAULT NULL,
  shop VARCHAR(191) DEFAULT NULL,
  total_price DECIMAL(14,2) DEFAULT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  currency VARCHAR(16) DEFAULT 'USD',
  commission_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  platform_order_id VARCHAR(120) DEFAULT NULL,
  customer_email VARCHAR(191) DEFAULT NULL,
  payout_status VARCHAR(40) NOT NULL DEFAULT 'unpaid',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_orders_platform_order (store_id, platform_order_id),
  KEY idx_orders_store_id (store_id),
  KEY idx_orders_ambassador_id (ambassador_id),
  KEY idx_orders_referral_code (referral_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payouts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id INT UNSIGNED NOT NULL,
  ambassador_id INT UNSIGNED NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'requested',
  invoice_url VARCHAR(255) DEFAULT NULL,
  paid_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_payouts_store_id (store_id),
  KEY idx_payouts_ambassador_id (ambassador_id),
  KEY idx_payouts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE referrals (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id INT UNSIGNED NOT NULL,
  ambassador_id INT UNSIGNED DEFAULT NULL,
  referral_code VARCHAR(120) NOT NULL,
  referrer_email VARCHAR(191) DEFAULT NULL,
  referred_email VARCHAR(191) DEFAULT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_referrals_store_id (store_id),
  KEY idx_referrals_ambassador_id (ambassador_id),
  KEY idx_referrals_referral_code (referral_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED DEFAULT NULL,
  email VARCHAR(191) NOT NULL,
  token VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_password_resets_token (token),
  KEY idx_password_resets_email (email),
  KEY idx_password_resets_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clicks (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id INT UNSIGNED NOT NULL,
  ambassador_id INT UNSIGNED DEFAULT NULL,
  referral_code VARCHAR(120) DEFAULT NULL,
  ip_address VARCHAR(64) DEFAULT NULL,
  user_agent VARCHAR(512) DEFAULT NULL,
  referrer_url VARCHAR(512) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_clicks_store_id (store_id),
  KEY idx_clicks_ambassador_id (ambassador_id),
  KEY idx_clicks_referral_code (referral_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users
  ADD CONSTRAINT fk_users_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE stores
  ADD CONSTRAINT fk_stores_owner_user FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT fk_stores_admin_user FOREIGN KEY (store_admin_user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE admin_users
  ADD CONSTRAINT fk_admin_users_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT fk_admin_users_ambassador FOREIGN KEY (ambassador_id) REFERENCES ambassadors(id) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE ambassadors
  ADD CONSTRAINT fk_ambassadors_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT fk_ambassadors_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE users
  ADD CONSTRAINT fk_users_ambassador FOREIGN KEY (ambassador_id) REFERENCES ambassadors(id) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE orders
  ADD CONSTRAINT fk_orders_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT fk_orders_ambassador FOREIGN KEY (ambassador_id) REFERENCES ambassadors(id) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE payouts
  ADD CONSTRAINT fk_payouts_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT fk_payouts_ambassador FOREIGN KEY (ambassador_id) REFERENCES ambassadors(id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE referrals
  ADD CONSTRAINT fk_referrals_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT fk_referrals_ambassador FOREIGN KEY (ambassador_id) REFERENCES ambassadors(id) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE password_resets
  ADD CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE clicks
  ADD CONSTRAINT fk_clicks_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT fk_clicks_ambassador FOREIGN KEY (ambassador_id) REFERENCES ambassadors(id) ON DELETE SET NULL ON UPDATE CASCADE;

INSERT INTO users (email, store_id, ambassador_id, phone, name, password_hash, user_role, user_type, status, must_change_password, created_at, updated_at)
VALUES ('admin@trustai.no', NULL, NULL, NULL, 'TrustAI Admin', '$2y$12$Ugnq3OyCsnw3bOBfxpAQaei/4mN.osHyEDz0xB8gZQjdRvVm43f9u', 'super_admin', 'internal', 'active', 0, NOW(), NOW());

INSERT INTO stores (
  name, url, domain, platform, public_url,
  contact_name, contact_title, contact_email, contact_phone,
  default_commission_percent, commission_percent, commission_amount,
  owner_user_id, store_admin_user_id,
  shopify_domain, shopify_shop, access_token, webhook_secret,
  status, created_at, updated_at
) VALUES (
  'TrustAI Demo Store', 'https://demo.trustai.no', 'demo.trustai.no', 'Shopify', 'https://demo.trustai.no',
  'Demo Owner', 'Ecommerce Manager', 'owner@trustai.no', '+47 40000000',
  20.00, 20.00, 0.00,
  1, NULL,
  'demo-trustai.myshopify.com', 'demo-trustai', NULL, NULL,
  'active', NOW(), NOW()
);

INSERT INTO ambassadors (
  user_id, store_id, name, ambassador_name, email,
  code, referral_code,
  status, approved_at,
  commission_percent, commission_amount,
  total_revenue, sales_count,
  created_at, updated_at
) VALUES (
  NULL, 1, 'Test Ambassador', 'Test Ambassador', 'ambassador@trustai.no',
  'TRUSTTEST', 'TRUSTTEST',
  'approved', NOW(),
  20.00, 0.00,
  0.00, 0,
  NOW(), NOW()
);

SET FOREIGN_KEY_CHECKS = 1;
