<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/_auth_common.php';

// ─── Demo user / store constants ──────────────────────────────────────────────

define('DEMO_AMB_EMAIL',     'demo.ambassador@trustai.no');
define('DEMO_AMB_PHONE',     '+4799990001');
define('DEMO_AMB_NAME',      'Demo Ambassadør');
define('DEMO_AMB_VIPPS_SUB', 'demo_vipps_ambassador');

define('DEMO_STORE_EMAIL',     'demo.store@trustai.no');
define('DEMO_STORE_PHONE',     '+4799990002');
define('DEMO_STORE_OWNER',     'Demo Butikk Eier');
define('DEMO_STORE_VIPPS_SUB', 'demo_vipps_store');

define('DEMO_STORE_DOMAIN',   'demo.trustai.no');
define('DEMO_STORE_NAME',     'Demo Butikk');
define('DEMO_STORE_URL',      'https://demo.trustai.no');
define('DEMO_STORE_PLATFORM', 'demo');
define('DEMO_COMMISSION',     10.0);
define('DEMO_AMB_REF_CODE',  'DEMO_AMB');

// ─── Mode check ───────────────────────────────────────────────────────────────

function isDemoMode(): bool
{
    $envApp = strtolower((string)(getenv('APP_ENV') ?: (defined('APP_ENV') ? APP_ENV : '')));
    if (in_array($envApp, ['local', 'dev', 'development', 'test'], true)) {
        return true;
    }
    $demoEnv = getenv('DEMO_MODE');
    if ($demoEnv !== false && $demoEnv !== '') {
        return in_array(strtolower($demoEnv), ['1', 'true', 'yes', 'on'], true);
    }
    if (defined('DEMO_MODE')) {
        return (bool)constant('DEMO_MODE');
    }
    return false;
}

function requireDemoMode(): void
{
    if (!isDemoMode()) {
        if (!headers_sent()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['ok' => false, 'error' => 'demo_mode_disabled']);
        exit;
    }
}

// ─── Schema migration ─────────────────────────────────────────────────────────

function demoEnsureSchema(PDO $pdo): void
{
    $readCols = $pdo->prepare(
        'SELECT column_name FROM information_schema.columns
          WHERE table_schema = DATABASE() AND table_name = :t'
    );

    // is_demo flag on all transactional tables
    $demoCols = ['users', 'stores', 'ambassadors', 'leads', 'orders', 'clicks', 'payouts'];
    foreach ($demoCols as $tbl) {
        // Verify table exists before ALTER
        $chk = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.tables
              WHERE table_schema = DATABASE() AND table_name = :t"
        );
        $chk->execute(['t' => $tbl]);
        if ((int)$chk->fetchColumn() === 0) continue;

        $readCols->execute(['t' => $tbl]);
        $cols = $readCols->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('is_demo', $cols, true)) {
            $pdo->exec("ALTER TABLE `$tbl` ADD COLUMN `is_demo` TINYINT(1) NOT NULL DEFAULT 0");
        }
    }

    // Extra onboarding status columns on stores
    $readCols->execute(['t' => 'stores']);
    $storeCols = $readCols->fetchAll(PDO::FETCH_COLUMN);
    $storeExtras = [
        'onboarding_status' => "VARCHAR(40) NOT NULL DEFAULT 'pending'",
        'webhook_status'    => "VARCHAR(40) NOT NULL DEFAULT 'pending'",
        'script_status'     => "VARCHAR(40) NOT NULL DEFAULT 'pending'",
        'tracking_status'   => "VARCHAR(40) NOT NULL DEFAULT 'pending'",
    ];
    foreach ($storeExtras as $col => $def) {
        if (!in_array($col, $storeCols, true)) {
            $pdo->exec("ALTER TABLE `stores` ADD COLUMN `$col` $def");
        }
    }
}

// ─── Demo store ───────────────────────────────────────────────────────────────

function demoGetOrCreateStore(PDO $pdo): int
{
    $stmt = $pdo->prepare('SELECT id FROM stores WHERE domain = :d LIMIT 1');
    $stmt->execute(['d' => DEMO_STORE_DOMAIN]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $sid = (int)$row['id'];
        // Ensure the store is flagged as demo and fully onboarded.
        $pdo->prepare(
            'UPDATE stores
                SET is_demo=1, status="active",
                    onboarding_status="completed",
                    webhook_status="verified",
                    script_status="verified",
                    tracking_status="verified",
                    default_commission_percent=:comm,
                    name=:name, platform=:platform,
                    public_url=:url, url=:url,
                    updated_at=NOW()
              WHERE id=:id'
        )->execute([
            'comm'     => DEMO_COMMISSION,
            'name'     => DEMO_STORE_NAME,
            'platform' => DEMO_STORE_PLATFORM,
            'url'      => DEMO_STORE_URL,
            'id'       => $sid,
        ]);
        return $sid;
    }

    $pdo->prepare(
        'INSERT INTO stores
            (name, domain, url, public_url, platform, status,
             default_commission_percent, commission_percent,
             contact_name, contact_email, contact_phone,
             is_demo, onboarding_status, webhook_status, script_status, tracking_status,
             created_at, updated_at)
         VALUES
            (:name, :domain, :url, :url, :platform, "active",
             :comm, :comm,
             :cname, :cemail, :cphone,
             1, "completed", "verified", "verified", "verified",
             NOW(), NOW())'
    )->execute([
        'name'     => DEMO_STORE_NAME,
        'domain'   => DEMO_STORE_DOMAIN,
        'url'      => DEMO_STORE_URL,
        'platform' => DEMO_STORE_PLATFORM,
        'comm'     => DEMO_COMMISSION,
        'cname'    => DEMO_STORE_OWNER,
        'cemail'   => DEMO_STORE_EMAIL,
        'cphone'   => DEMO_STORE_PHONE,
    ]);
    return (int)$pdo->lastInsertId();
}

// ─── Demo user helpers ────────────────────────────────────────────────────────

function demoGetOrCreateUser(PDO $pdo, string $email, string $name, string $phone, string $vippsSub, string $role): array
{
    $stmt = $pdo->prepare(
        'SELECT id, email, role, status, store_id, ambassador_id FROM users
          WHERE email = :e OR vipps_sub = :s
          LIMIT 1'
    );
    $stmt->execute(['e' => $email, 's' => $vippsSub]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($user) {
        $pdo->prepare(
            'UPDATE users
                SET name=:name, full_name=:name, phone=:phone, phone_number=:phone,
                    vipps_sub=:sub, provider="demo_vipps", provider_id=:sub,
                    role=:role, is_demo=1, status="active",
                    birth_date="2000-01-01", updated_at=NOW()
              WHERE id=:id'
        )->execute([
            'name'  => $name,
            'phone' => $phone,
            'sub'   => $vippsSub,
            'role'  => $role,
            'id'    => (int)$user['id'],
        ]);
        $user['role'] = $role;
        return $user;
    }

    $pdo->prepare(
        'INSERT INTO users
            (email, name, full_name, phone, phone_number,
             vipps_sub, provider, provider_id,
             role, status, is_demo, birth_date,
             password_hash, created_at, updated_at)
         VALUES
            (:email, :name, :name, :phone, :phone,
             :sub, "demo_vipps", :sub,
             :role, "active", 1, "2000-01-01",
             "", NOW(), NOW())'
    )->execute([
        'email' => $email,
        'name'  => $name,
        'phone' => $phone,
        'sub'   => $vippsSub,
        'role'  => $role,
    ]);
    $newId = (int)$pdo->lastInsertId();
    return [
        'id'           => $newId,
        'email'        => $email,
        'role'         => $role,
        'status'       => 'active',
        'store_id'     => null,
        'ambassador_id' => null,
    ];
}

// ─── Demo ambassador record ───────────────────────────────────────────────────

function demoGetOrCreateAmbassador(PDO $pdo, int $userId, int $storeId, string $email, string $name, string $phone): int
{
    $stmt = $pdo->prepare(
        'SELECT id FROM ambassadors
          WHERE store_id=:sid AND (user_id=:uid OR LOWER(email)=:e)
          LIMIT 1'
    );
    $stmt->execute(['sid' => $storeId, 'uid' => $userId, 'e' => strtolower($email)]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $aid = (int)$row['id'];
        $pdo->prepare(
            'UPDATE ambassadors
                SET status="approved", approved_at=COALESCE(approved_at, NOW()),
                    is_demo=1, user_id=:uid, commission_percent=:comm, updated_at=NOW()
              WHERE id=:id'
        )->execute(['uid' => $userId, 'comm' => DEMO_COMMISSION, 'id' => $aid]);
        return $aid;
    }

    $pdo->prepare(
        'INSERT INTO ambassadors
            (user_id, store_id, name, ambassador_name, email, phone,
             code, referral_code, status, approved_at,
             commission_percent, is_demo, created_at, updated_at)
         VALUES
            (:uid, :sid, :name, :name, :email, :phone,
             :code, :code, "approved", NOW(),
             :comm, 1, NOW(), NOW())'
    )->execute([
        'uid'   => $userId,
        'sid'   => $storeId,
        'name'  => $name,
        'email' => $email,
        'phone' => $phone,
        'code'  => DEMO_AMB_REF_CODE,
        'comm'  => DEMO_COMMISSION,
    ]);
    return (int)$pdo->lastInsertId();
}

// ─── Demo data seeding ────────────────────────────────────────────────────────

function demoSeedData(PDO $pdo, int $storeId, int $ambassadorId): void
{
    // Idempotent: check if demo data already exists
    $hasData = (bool)$pdo->prepare(
        'SELECT COUNT(*) FROM leads WHERE store_id=:sid AND is_demo=1'
    )->execute(['sid' => $storeId]) && (int)$pdo->query(
        'SELECT COUNT(*) FROM leads WHERE store_id=' . $storeId . ' AND is_demo=1'
    )->fetchColumn() > 0;

    // Clicks (7 demo clicks)
    $existingClicks = (int)$pdo->query(
        'SELECT COUNT(*) FROM clicks WHERE store_id=' . $storeId . ' AND is_demo=1'
    )->fetchColumn();
    if ($existingClicks < 7) {
        $insClick = $pdo->prepare(
            'INSERT IGNORE INTO clicks
                (store_id, ambassador_id, referral_code, source, ip_hash, user_agent, is_demo, created_at)
             VALUES (:sid, :aid, :code, "demo_seed", :ip, "demo-browser/1.0", 1, :ts)'
        );
        $baseTs = strtotime('-14 days');
        for ($i = 0; $i < 7; $i++) {
            $insClick->execute([
                'sid'  => $storeId,
                'aid'  => $ambassadorId,
                'code' => DEMO_AMB_REF_CODE,
                'ip'   => hash('sha256', 'demo-click-' . $i),
                'ts'   => date('Y-m-d H:i:s', $baseTs + ($i * 43200)),
            ]);
        }
    }

    // Leads (4 demo leads with different statuses)
    $existingLeads = (int)$pdo->query(
        'SELECT COUNT(*) FROM leads WHERE store_id=' . $storeId . ' AND is_demo=1'
    )->fetchColumn();
    if ($existingLeads < 4) {
        $insLead = $pdo->prepare(
            'INSERT INTO leads
                (store_id, ambassador_id, company_name, contact_person,
                 contact_email, contact_phone, status,
                 source, offer_amount, commission_percent, commission_amount,
                 is_demo, created_at)
             VALUES
                (:sid, :aid, :company, :person,
                 :email, :phone, :status,
                 "demo_ambassador", :offer, :cpct, :camount,
                 1, :ts)'
        );
        $leadDefs = [
            ['Demo Bedrift AS',     'open',          null,   null,  null],
            ['Norsk Handel AS',     'meeting_booked', null,  null,  null],
            ['Digital Partner AS',  'offer_sent',   15000.0, 10.0, 1500.0],
            ['Handels Partner AS',  'approved',     22000.0, 10.0, 2200.0],
        ];
        $baseTs = strtotime('-12 days');
        foreach ($leadDefs as $idx => [$company, $status, $offer, $cpct, $camount]) {
            $insLead->execute([
                'sid'     => $storeId,
                'aid'     => $ambassadorId,
                'company' => $company,
                'person'  => 'Demo Kontakt',
                'email'   => 'kontakt' . ($idx + 1) . '@demo.no',
                'phone'   => '+4712345' . ($idx + 100),
                'status'  => $status,
                'offer'   => $offer,
                'cpct'    => $cpct,
                'camount' => $camount,
                'ts'      => date('Y-m-d H:i:s', $baseTs + ($idx * 86400 * 2)),
            ]);
        }
    }

    // Order (1 demo order)
    $existingOrders = (int)$pdo->query(
        'SELECT COUNT(*) FROM orders WHERE store_id=' . $storeId . ' AND is_demo=1'
    )->fetchColumn();
    if ($existingOrders < 1) {
        $amount = 2200.0;
        $commission = round($amount * DEMO_COMMISSION / 100, 2);
        $pdo->prepare(
            'INSERT INTO orders
                (store_id, ambassador_id, referral_code,
                 platform_order_id, customer_name, customer_email,
                 amount, total_price, commission_amount, currency,
                 payout_status, is_demo, created_at)
             VALUES
                (:sid, :aid, :code,
                 "demo-order-001", "Demo Kunde", "kunde@demo.no",
                 :amount, :amount, :comm, "NOK",
                 "pending", 1, NOW())'
        )->execute([
            'sid'    => $storeId,
            'aid'    => $ambassadorId,
            'code'   => DEMO_AMB_REF_CODE,
            'amount' => $amount,
            'comm'   => $commission,
        ]);
    }

    // Payout (1 demo payout – status 'requested' = tilgjengelig)
    $existingPayouts = (int)$pdo->query(
        'SELECT COUNT(*) FROM payouts WHERE store_id=' . $storeId . ' AND is_demo=1'
    )->fetchColumn();
    if ($existingPayouts < 1) {
        $pdo->prepare(
            'INSERT INTO payouts
                (store_id, ambassador_id, amount, status, is_demo, created_at)
             VALUES
                (:sid, :aid, :amount, "requested", 1, NOW())'
        )->execute([
            'sid'    => $storeId,
            'aid'    => $ambassadorId,
            'amount' => round(2200.0 * DEMO_COMMISSION / 100, 2),
        ]);
    }
}

// ─── Session ──────────────────────────────────────────────────────────────────

function demoStartSession(array $user): void
{
    trustaiStartSessionForUser($user);
    $_SESSION['is_demo']            = true;
    $_SESSION['vipps_age_verified'] = true;
    $_SESSION['vipps_needs_age_confirm'] = false;
}
