-- Migration: 2026-05-12 — Super admin account cleanup
-- • Promotes ks@trustai.no to super_admin (primary login, password-only)
-- • Deactivates the legacy admin@trustai.no account
-- • Strips Vipps / personal data from the test store_admin (khabat.staee@gmail.com)
-- • Marks the Shopify test store (khabatsetaei.myshopify.com) as demo/inactive
--
-- Password for ks@trustai.no is set out-of-band (not stored in migration files).
-- To reset: php -r "echo password_hash('NEW_PW', PASSWORD_BCRYPT, ['cost'=>12]);"
--           then: UPDATE users SET password_hash='...' WHERE email='ks@trustai.no';

-- 1. Promote ks@trustai.no to super_admin, clear any personal / Vipps fields
UPDATE users SET
  role          = 'super_admin',
  status        = 'active',
  name          = COALESCE(NULLIF(TRIM(name),''), 'Super Admin'),
  full_name     = COALESCE(NULLIF(TRIM(full_name),''), 'Super Admin'),
  provider      = NULL,
  provider_id   = NULL,
  vipps_sub     = NULL,
  phone         = NULL,
  phone_number  = NULL,
  birth_date    = NULL,
  store_id      = NULL,
  ambassador_id = NULL,
  is_demo       = 0,
  updated_at    = NOW()
WHERE email = 'ks@trustai.no';

-- 2. Deactivate the old admin@trustai.no seed account
UPDATE users SET
  status     = 'inactive',
  updated_at = NOW()
WHERE email = 'admin@trustai.no' AND role = 'super_admin';

-- 3. Strip personal Vipps data from the Vipps-test store_admin, mark inactive
UPDATE users SET
  vipps_sub    = NULL,
  provider     = NULL,
  provider_id  = NULL,
  phone        = NULL,
  phone_number = NULL,
  birth_date   = NULL,
  is_demo      = 1,
  status       = 'inactive',
  updated_at   = NOW()
WHERE email = 'khabat.staee@gmail.com';

-- 4. Mark the development Shopify store and its ambassadors as demo/inactive
UPDATE stores SET
  is_demo    = 1,
  status     = 'inactive',
  updated_at = NOW()
WHERE domain = 'khabatsetaei.myshopify.com';

UPDATE ambassadors SET
  is_demo    = 1,
  updated_at = NOW()
WHERE store_id = (SELECT id FROM stores WHERE domain = 'khabatsetaei.myshopify.com' LIMIT 1);
