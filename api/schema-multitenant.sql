CREATE TABLE IF NOT EXISTS stores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  domain VARCHAR(190) NOT NULL UNIQUE,
  platform VARCHAR(50) NOT NULL,
  owner_user_id INT NULL,
  default_commission_percent DECIMAL(5,2) NOT NULL DEFAULT 20.00,
  status VARCHAR(40) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  role ENUM('super_admin','store_admin','ambassador') NOT NULL,
  store_id INT NULL,
  ambassador_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_store_id (store_id),
  INDEX idx_users_ambassador_id (ambassador_id)
);

CREATE TABLE IF NOT EXISTS ambassadors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NOT NULL,
  user_id INT NULL,
  name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(50) NULL,
  referral_code VARCHAR(120) NOT NULL,
  status ENUM('pending','approved','paused','rejected') NOT NULL DEFAULT 'pending',
  commission_percent DECIMAL(5,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approved_at DATETIME NULL,
  UNIQUE KEY uniq_store_ref_code (store_id, referral_code),
  INDEX idx_amb_store (store_id)
);

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NOT NULL,
  ambassador_id INT NULL,
  referral_code VARCHAR(120) NULL,
  platform_order_id VARCHAR(190) NOT NULL,
  customer_name VARCHAR(190) NULL,
  customer_email VARCHAR(190) NULL,
  amount DECIMAL(12,2) NOT NULL,
  commission_amount DECIMAL(12,2) NOT NULL,
  payout_status VARCHAR(40) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_store_platform_order (store_id, platform_order_id),
  INDEX idx_orders_store (store_id),
  INDEX idx_orders_ambassador (ambassador_id)
);

CREATE TABLE IF NOT EXISTS clicks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NOT NULL,
  ambassador_id INT NULL,
  referral_code VARCHAR(120) NULL,
  source VARCHAR(120) NULL,
  ip_hash VARCHAR(190) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_clicks_store (store_id),
  INDEX idx_clicks_ambassador (ambassador_id)
);

CREATE TABLE IF NOT EXISTS payouts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NOT NULL,
  ambassador_id INT NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  status ENUM('requested','approved','paid','rejected') NOT NULL DEFAULT 'requested',
  invoice_url VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  paid_at DATETIME NULL,
  INDEX idx_payouts_store (store_id),
  INDEX idx_payouts_ambassador (ambassador_id)
);
