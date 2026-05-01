-- TrustAI full reset (phpMyAdmin import-safe)
-- Drops existing foreign keys dynamically, then drops/recreates core tables without FKs.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Dynamically drop all foreign key constraints in current database
SET @fk_sql = (
    SELECT IFNULL(
        GROUP_CONCAT(
            CONCAT(
                'ALTER TABLE `', kcu.TABLE_NAME, '` DROP FOREIGN KEY `', kcu.CONSTRAINT_NAME, '`'
            ) SEPARATOR '; '
        ),
        'SELECT 1'
    )
    FROM information_schema.KEY_COLUMN_USAGE kcu
    WHERE kcu.CONSTRAINT_SCHEMA = DATABASE()
      AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
);

SET @fk_sql = CONCAT(@fk_sql, ';');
PREPARE stmt_fk_drop FROM @fk_sql;
EXECUTE stmt_fk_drop;
DEALLOCATE PREPARE stmt_fk_drop;

DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `payouts`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `clicks`;
DROP TABLE IF EXISTS `referrals`;
DROP TABLE IF EXISTS `ambassadors`;
DROP TABLE IF EXISTS `admin_users`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `stores`;

CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) DEFAULT NULL,
  `role` VARCHAR(40) NOT NULL DEFAULT 'ambassador',
  `user_role` VARCHAR(40) DEFAULT NULL,
  `user_type` VARCHAR(40) DEFAULT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'active',
  `provider` VARCHAR(40) DEFAULT NULL,
  `provider_id` VARCHAR(190) DEFAULT NULL,
  `store_id` INT UNSIGNED DEFAULT NULL,
  `ambassador_id` INT UNSIGNED DEFAULT NULL,
  `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_store_id` (`store_id`),
  KEY `idx_users_ambassador_id` (`ambassador_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `admin_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `name` VARCHAR(120) DEFAULT NULL,
  `role` VARCHAR(40) NOT NULL DEFAULT 'super_admin',
  `status` VARCHAR(40) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stores` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(190) NOT NULL,
  `domain` VARCHAR(255) DEFAULT NULL,
  `public_url` VARCHAR(255) DEFAULT NULL,
  `url` VARCHAR(255) DEFAULT NULL,
  `shopify_domain` VARCHAR(255) DEFAULT NULL,
  `platform` VARCHAR(40) NOT NULL DEFAULT 'shopify',
  `owner_user_id` INT UNSIGNED DEFAULT NULL,
  `store_admin_user_id` INT UNSIGNED DEFAULT NULL,
  `default_commission_percent` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  `commission_percent` DECIMAL(5,2) DEFAULT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'active',
  `contact_name` VARCHAR(190) DEFAULT NULL,
  `contact_email` VARCHAR(190) DEFAULT NULL,
  `contact_phone` VARCHAR(60) DEFAULT NULL,
  `contact_title` VARCHAR(120) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_stores_domain` (`domain`),
  KEY `idx_stores_owner_user_id` (`owner_user_id`),
  KEY `idx_stores_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ambassadors` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `store_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(190) NOT NULL,
  `ambassador_name` VARCHAR(190) DEFAULT NULL,
  `email` VARCHAR(190) NOT NULL,
  `phone` VARCHAR(60) DEFAULT NULL,
  `code` VARCHAR(80) DEFAULT NULL,
  `referral_code` VARCHAR(80) DEFAULT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'pending',
  `commission_percent` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ambassadors_store_id` (`store_id`),
  KEY `idx_ambassadors_user_id` (`user_id`),
  KEY `idx_ambassadors_email` (`email`),
  KEY `idx_ambassadors_referral_code` (`referral_code`),
  UNIQUE KEY `uniq_ambassadors_store_referral` (`store_id`, `referral_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `store_id` INT UNSIGNED NOT NULL,
  `ambassador_id` INT UNSIGNED DEFAULT NULL,
  `referral_code` VARCHAR(80) DEFAULT NULL,
  `platform_order_id` VARCHAR(120) NOT NULL,
  `customer_name` VARCHAR(190) DEFAULT NULL,
  `customer_email` VARCHAR(190) DEFAULT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `commission_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `payout_status` VARCHAR(40) NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_orders_store_platform_order` (`store_id`, `platform_order_id`),
  KEY `idx_orders_store_id` (`store_id`),
  KEY `idx_orders_ambassador_id` (`ambassador_id`),
  KEY `idx_orders_referral_code` (`referral_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payouts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `store_id` INT UNSIGNED NOT NULL,
  `ambassador_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'requested',
  `invoice_url` VARCHAR(255) DEFAULT NULL,
  `invoice_file_path` VARCHAR(255) DEFAULT NULL,
  `comment` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `paid_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payouts_store_id` (`store_id`),
  KEY `idx_payouts_ambassador_id` (`ambassador_id`),
  KEY `idx_payouts_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `referrals` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `store_id` INT UNSIGNED DEFAULT NULL,
  `ambassador_id` INT UNSIGNED DEFAULT NULL,
  `ref_code` VARCHAR(80) NOT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_referrals_store_id` (`store_id`),
  KEY `idx_referrals_ambassador_id` (`ambassador_id`),
  KEY `idx_referrals_ref_code` (`ref_code`),
  KEY `idx_referrals_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clicks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `store_id` INT UNSIGNED DEFAULT NULL,
  `ambassador_id` INT UNSIGNED DEFAULT NULL,
  `referral_code` VARCHAR(80) DEFAULT NULL,
  `ref_code` VARCHAR(80) DEFAULT NULL,
  `source` VARCHAR(100) DEFAULT NULL,
  `ip_hash` CHAR(64) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_clicks_store_id` (`store_id`),
  KEY `idx_clicks_ambassador_id` (`ambassador_id`),
  KEY `idx_clicks_referral_code` (`referral_code`),
  KEY `idx_clicks_ref_code` (`ref_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_resets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_password_resets_user_id` (`user_id`),
  KEY `idx_password_resets_token_hash` (`token_hash`),
  KEY `idx_password_resets_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed super admin login in users table for auth/login API
INSERT INTO `users` (`email`, `password_hash`, `role`, `status`, `store_id`, `ambassador_id`, `must_change_password`, `created_at`, `updated_at`)
VALUES (
  'admin@trustai.no',
  '$2y$10$qjQ6Sd6yW5ReMYAHCn9NteqFhM4A7ohLxQhwQw1U4GqCGM2spxZ9q',
  'super_admin',
  'active',
  NULL,
  NULL,
  0,
  NOW(),
  NOW()
);

-- Optional mirrored admin row for legacy admin_users usage
INSERT INTO `admin_users` (`email`, `password_hash`, `name`, `role`, `status`, `created_at`, `updated_at`)
VALUES (
  'admin@trustai.no',
  '$2y$10$qjQ6Sd6yW5ReMYAHCn9NteqFhM4A7ohLxQhwQw1U4GqCGM2spxZ9q',
  'TrustAI Super Admin',
  'super_admin',
  'active',
  NOW(),
  NOW()
);

-- Demo store
INSERT INTO `stores` (
  `name`, `domain`, `public_url`, `url`, `shopify_domain`, `platform`, `owner_user_id`, `store_admin_user_id`,
  `default_commission_percent`, `commission_percent`, `status`, `contact_name`, `contact_email`, `contact_phone`, `contact_title`, `created_at`, `updated_at`
) VALUES (
  'TrustAI Demo Store',
  'demo.trustai.no',
  'https://demo.trustai.no',
  'https://demo.trustai.no',
  'demo.myshopify.com',
  'shopify',
  1,
  1,
  12.50,
  12.50,
  'active',
  'Demo Store Admin',
  'admin@trustai.no',
  '+47 000 00 000',
  'Store Owner',
  NOW(),
  NOW()
);

-- Demo ambassador user
INSERT INTO `users` (`email`, `password_hash`, `role`, `status`, `store_id`, `ambassador_id`, `must_change_password`, `created_at`, `updated_at`)
VALUES (
  'ambassador.demo@trustai.no',
  '$2y$10$qjQ6Sd6yW5ReMYAHCn9NteqFhM4A7ohLxQhwQw1U4GqCGM2spxZ9q',
  'ambassador',
  'active',
  1,
  NULL,
  0,
  NOW(),
  NOW()
);

-- Demo ambassador profile
INSERT INTO `ambassadors` (
  `store_id`, `user_id`, `name`, `ambassador_name`, `email`, `phone`, `code`, `referral_code`, `status`, `commission_percent`, `created_at`, `approved_at`, `updated_at`
) VALUES (
  1,
  2,
  'Demo Ambassador',
  'Demo Ambassador',
  'ambassador.demo@trustai.no',
  '+47 111 11 111',
  'DEMOAMB',
  'DEMOAMB',
  'approved',
  12.50,
  NOW(),
  NOW(),
  NOW()
);

UPDATE `users` SET `ambassador_id` = 1 WHERE `id` = 2;

SET FOREIGN_KEY_CHECKS = 1;
