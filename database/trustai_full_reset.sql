SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS payouts;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS clicks;
DROP TABLE IF EXISTS referrals;
DROP TABLE IF EXISTS ambassadors;
DROP TABLE IF EXISTS admin_users;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS stores;

SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) DEFAULT NULL,
  role VARCHAR(50) DEFAULT 'store_admin',
  user_role VARCHAR(50) DEFAULT NULL,
  user_type VARCHAR(50) DEFAULT NULL,
  status VARCHAR(24) NOT NULL DEFAULT 'active',
  store_id INT UNSIGNED DEFAULT NULL,
  ambassador_id INT UNSIGNED DEFAULT NULL,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_store_id (store_id),
  KEY idx_users_ambassador_id (ambassador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(50) NOT NULL DEFAULT 'super_admin',
  status VARCHAR(24) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admin_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stores (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_user_id INT UNSIGNED DEFAULT NULL,
  store_admin_user_id INT UNSIGNED DEFAULT NULL,
  name VARCHAR(190) NOT NULL,
  domain VARCHAR(255) NOT NULL,
  url VARCHAR(255) DEFAULT NULL,
  public_url VARCHAR(255) DEFAULT NULL,
  shopify_domain VARCHAR(255) DEFAULT NULL,
  platform VARCHAR(50) NOT NULL DEFAULT 'shopify',
  status VARCHAR(24) NOT NULL DEFAULT 'active',
  default_commission_percent DECIMAL(8,2) NOT NULL DEFAULT 10.00,
  commission_percent DECIMAL(8,2) DEFAULT NULL,
  contact_name VARCHAR(190) DEFAULT NULL,
  contact_email VARCHAR(190) DEFAULT NULL,
  contact_phone VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_stores_domain (domain),
  KEY idx_stores_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ambassadors (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED DEFAULT NULL,
  name VARCHAR(190) NOT NULL,
  ambassador_name VARCHAR(190) DEFAULT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  code VARCHAR(120) DEFAULT NULL,
  referral_code VARCHAR(120) DEFAULT NULL,
  status VARCHAR(24) NOT NULL DEFAULT 'pending',
  commission_percent DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approved_at DATETIME DEFAULT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ambassadors_store_id (store_id),
  KEY idx_ambassadors_user_id (user_id),
  KEY idx_ambassadors_email (email),
  KEY idx_ambassadors_referral_code (referral_code),
  KEY idx_ambassadors_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id INT UNSIGNED NOT NULL,
  ambassador_id INT UNSIGNED DEFAULT NULL,
  referral_code VARCHAR(120) DEFAULT NULL,
  platform_order_id VARCHAR(120) DEFAULT NULL,
  order_id VARCHAR(120) DEFAULT NULL,
  customer_name VARCHAR(190) DEFAULT NULL,
  customer_email VARCHAR(190) DEFAULT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  payout_status VARCHAR(24) NOT NULL DEFAULT 'pending',
  currency VARCHAR(12) NOT NULL DEFAULT 'NOK',
  status VARCHAR(24) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_orders_store_id (store_id),
  KEY idx_orders_ambassador_id (ambassador_id),
  KEY idx_orders_referral_code (referral_code),
  KEY idx_orders_platform_order_id (platform_order_id),
  KEY idx_orders_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payouts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id INT UNSIGNED NOT NULL,
  ambassador_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  status VARCHAR(24) NOT NULL DEFAULT 'requested',
  invoice_url VARCHAR(255) DEFAULT NULL,
  invoice_file_path VARCHAR(255) DEFAULT NULL,
  comment TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  paid_at DATETIME DEFAULT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_payouts_store_id (store_id),
  KEY idx_payouts_ambassador_id (ambassador_id),
  KEY idx_payouts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE referrals (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id INT UNSIGNED DEFAULT NULL,
  ambassador_id INT UNSIGNED DEFAULT NULL,
  ref_code VARCHAR(120) NOT NULL,
  email VARCHAR(190) DEFAULT NULL,
  order_id VARCHAR(120) DEFAULT NULL,
  sale_amount DECIMAL(12,2) DEFAULT NULL,
  commission_amount DECIMAL(12,2) DEFAULT NULL,
  status VARCHAR(24) DEFAULT 'tracked',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_referrals_store_id (store_id),
  KEY idx_referrals_ambassador_id (ambassador_id),
  KEY idx_referrals_ref_code (ref_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clicks (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id INT UNSIGNED NOT NULL,
  ambassador_id INT UNSIGNED DEFAULT NULL,
  referral_code VARCHAR(120) DEFAULT NULL,
  ref_code VARCHAR(120) DEFAULT NULL,
  source VARCHAR(120) DEFAULT NULL,
  ip_hash VARCHAR(190) DEFAULT NULL,
  user_agent TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_clicks_store_id (store_id),
  KEY idx_clicks_ambassador_id (ambassador_id),
  KEY idx_clicks_referral_code (referral_code),
  KEY idx_clicks_ref_code (ref_code),
  KEY idx_clicks_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED DEFAULT NULL,
  email VARCHAR(190) NOT NULL,
  token VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_password_resets_token (token),
  KEY idx_password_resets_email (email),
  KEY idx_password_resets_user_id (user_id),
  KEY idx_password_resets_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO admin_users (email, password_hash, role, status, created_at, updated_at)
VALUES ('admin@trustai.no', '$2y$12$JRn7c0qTd7HsXUuDLGLhQuHabOFTC/E2Ho6TbmgSdiDDFhDrhTGcW', 'super_admin', 'active', NOW(), NOW());

INSERT INTO stores (
  owner_user_id,
  store_admin_user_id,
  name,
  domain,
  url,
  public_url,
  shopify_domain,
  platform,
  status,
  default_commission_percent,
  commission_percent,
  contact_name,
  contact_email,
  contact_phone,
  created_at,
  updated_at
)
VALUES (
  NULL,
  NULL,
  'TrustAI Demo Store',
  'demo.trustai.no',
  'https://demo.trustai.no',
  'https://demo.trustai.no',
  'demo.myshopify.com',
  'shopify',
  'active',
  10.00,
  10.00,
  'Demo Owner',
  'owner@demo.trustai.no',
  '+47 000 00 000',
  NOW(),
  NOW()
);

INSERT INTO ambassadors (
  store_id,
  user_id,
  name,
  ambassador_name,
  email,
  phone,
  code,
  referral_code,
  status,
  commission_percent,
  created_at,
  approved_at,
  updated_at
)
VALUES (
  1,
  NULL,
  'Demo Ambassador',
  'Demo Ambassador',
  'ambassador@demo.trustai.no',
  '+47 111 11 111',
  'demoamb',
  'demoamb001',
  'approved',
  10.00,
  NOW(),
  NOW(),
  NOW()
);
