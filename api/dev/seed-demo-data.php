<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

/**
 * Local JSON responder so we can return valid JSON even if auth bootstrap fails.
 */
$sendJson = static function (int $status, array $payload): void {
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

$authPath = __DIR__ . '/../_auth.php';
if (!is_file($authPath)) {
    $sendJson(500, ['ok' => false, 'error' => 'auth_bootstrap_missing']);
}

require_once $authPath;

if (!isset($pdo) || !$pdo instanceof PDO) {
    $bootstrapError = isset($dbBootstrapError) ? (string)$dbBootstrapError : '';
    $sendJson(500, ['ok' => false, 'error' => $bootstrapError === 'db_not_configured' ? 'db_not_configured' : 'database_connection_failed']);
}

$env = (string)(getenv('APP_ENV') ?: 'production');
$allow = in_array($env, ['dev', 'development', 'local', 'test'], true) || (($_GET['allow_seed'] ?? '') === '1');
if (!$allow) {
    $sendJson(403, ['ok' => false, 'error' => 'seed_disabled_outside_development']);
}

$user = function_exists('getCurrentUser') ? getCurrentUser() : null;
if ($user && ($user['role'] ?? '') !== 'super_admin') {
    $sendJson(403, ['ok' => false, 'error' => 'forbidden_role']);
}

$requiredTables = ['users', 'stores', 'ambassadors', 'orders', 'clicks', 'payouts'];
$placeholders = implode(',', array_fill(0, count($requiredTables), '?'));
$tableStmt = $pdo->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ($placeholders)");
$tableStmt->execute($requiredTables);
$foundTables = $tableStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
$missingTables = array_values(array_diff($requiredTables, $foundTables));

if ($missingTables !== []) {
    $sendJson(500, ['ok' => false, 'error' => 'tables_missing', 'missing' => $missingTables]);
}

final class SeedSqlException extends RuntimeException {
    public string $tableName;
    public string $operation;

    public function __construct(string $tableName, string $operation, string $message, ?Throwable $previous = null)
    {
        $this->tableName = $tableName;
        $this->operation = $operation;
        parent::__construct($message, 0, $previous);
    }
}

/**
 * Best-effort schema healing for older environments that still have partial tables.
 */
$requiredColumns = [
    'users' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'email' => 'VARCHAR(190) NOT NULL UNIQUE',
        'password_hash' => 'VARCHAR(255) NULL',
        'role' => "ENUM('super_admin','store_admin','ambassador') NOT NULL",
        'store_id' => 'INT NULL',
        'ambassador_id' => 'INT NULL',
        'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
    ],
    'stores' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'name' => 'VARCHAR(190) NOT NULL',
        'domain' => 'VARCHAR(190) NOT NULL UNIQUE',
        'platform' => 'VARCHAR(50) NOT NULL',
        'owner_user_id' => 'INT NULL',
        'default_commission_percent' => 'DECIMAL(5,2) NOT NULL DEFAULT 20.00',
        'status' => "VARCHAR(40) NOT NULL DEFAULT 'active'",
        'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
    ],
    'ambassadors' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'store_id' => 'INT NOT NULL',
        'user_id' => 'INT NULL',
        'name' => 'VARCHAR(190) NOT NULL',
        'email' => 'VARCHAR(190) NOT NULL',
        'phone' => 'VARCHAR(50) NULL',
        'referral_code' => 'VARCHAR(120) NOT NULL',
        'status' => "ENUM('pending','approved','paused','rejected') NOT NULL DEFAULT 'pending'",
        'commission_percent' => 'DECIMAL(5,2) NOT NULL',
        'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'approved_at' => 'DATETIME NULL',
    ],
    'orders' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'store_id' => 'INT NOT NULL',
        'ambassador_id' => 'INT NULL',
        'referral_code' => 'VARCHAR(120) NULL',
        'platform_order_id' => 'VARCHAR(190) NOT NULL',
        'customer_name' => 'VARCHAR(190) NULL',
        'customer_email' => 'VARCHAR(190) NULL',
        'amount' => 'DECIMAL(12,2) NOT NULL',
        'commission_amount' => 'DECIMAL(12,2) NOT NULL',
        'payout_status' => 'VARCHAR(40) NOT NULL',
        'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
    ],
    'clicks' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'store_id' => 'INT NOT NULL',
        'ambassador_id' => 'INT NULL',
        'referral_code' => 'VARCHAR(120) NULL',
        'source' => 'VARCHAR(120) NULL',
        'ip_hash' => 'VARCHAR(190) NULL',
        'user_agent' => 'VARCHAR(255) NULL',
        'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
    ],
    'payouts' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'store_id' => 'INT NOT NULL',
        'ambassador_id' => 'INT NOT NULL',
        'amount' => 'DECIMAL(12,2) NOT NULL',
        'status' => "ENUM('requested','approved','paid','rejected') NOT NULL DEFAULT 'requested'",
        'invoice_url' => 'VARCHAR(255) NULL',
        'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'paid_at' => 'DATETIME NULL',
    ],
];

$readColumns = $pdo->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name');
foreach ($requiredColumns as $tableName => $columns) {
    $readColumns->execute(['table_name' => $tableName]);
    $existingColumns = $readColumns->fetchAll(PDO::FETCH_COLUMN) ?: [];
    foreach ($columns as $columnName => $definition) {
        if (in_array($columnName, $existingColumns, true)) {
            continue;
        }

        // Avoid trying to patch missing primary keys in-place; require schema bootstrap instead.
        if ($columnName === 'id') {
            $sendJson(500, [
                'ok' => false,
                'error' => 'schema_missing_primary_key',
                'table' => $tableName,
                'message' => 'Missing required primary key column: id',
            ]);
        }

        $sql = sprintf('ALTER TABLE `%s` ADD COLUMN `%s` %s', $tableName, $columnName, $definition);
        $pdo->exec($sql);
    }
}

$pdo->beginTransaction();

try {
    $executeSeed = static function (PDOStatement $stmt, array $params, string $tableName, string $operation): void {
        try {
            $stmt->execute($params);
        } catch (Throwable $e) {
            throw new SeedSqlException($tableName, $operation, $e->getMessage(), $e);
        }
    };

    $superEmail = 'super@trustai.no';
    $demoPasswordHash = password_hash('Test12345!', PASSWORD_DEFAULT);

    $insertUser = $pdo->prepare('INSERT INTO users (email, password_hash, role, store_id, ambassador_id, created_at) VALUES (:email, :password_hash, :role, :store_id, :ambassador_id, NOW()) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role), store_id = VALUES(store_id), ambassador_id = VALUES(ambassador_id)');
    $executeSeed($insertUser, [
        'email' => $superEmail,
        'password_hash' => $demoPasswordHash,
        'role' => 'super_admin',
        'store_id' => null,
        'ambassador_id' => null,
    ], 'users', 'INSERT');

    $storeDefs = [
        ['name' => 'Demo Store Alpha', 'domain' => 'alpha-demo.myshopify.com', 'platform' => 'shopify', 'default_commission_percent' => 20.0, 'admin_email' => 'store1@trustai.no'],
        ['name' => 'Demo Store Beta', 'domain' => 'beta-demo.myshopify.com', 'platform' => 'shopify', 'default_commission_percent' => 18.0, 'admin_email' => 'store2@trustai.no'],
    ];

    $insertStore = $pdo->prepare('INSERT INTO stores (name, domain, platform, owner_user_id, default_commission_percent, status, created_at) VALUES (:name, :domain, :platform, NULL, :default_commission_percent, :status, NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name), platform = VALUES(platform), default_commission_percent = VALUES(default_commission_percent), status = VALUES(status)');
    $selectStoreId = $pdo->prepare('SELECT id FROM stores WHERE domain = :domain LIMIT 1');
    $updateStoreOwner = $pdo->prepare('UPDATE stores SET owner_user_id = :owner_user_id WHERE id = :id');

    $selectUser = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $insertAmb = $pdo->prepare('INSERT INTO ambassadors (store_id, user_id, name, email, phone, referral_code, status, commission_percent, created_at, approved_at) VALUES (:store_id, :user_id, :name, :email, :phone, :referral_code, :status, :commission_percent, NOW(), :approved_at) ON DUPLICATE KEY UPDATE status = VALUES(status), commission_percent = VALUES(commission_percent), user_id = VALUES(user_id), approved_at = VALUES(approved_at)');
    $selectAmb = $pdo->prepare('SELECT id FROM ambassadors WHERE store_id = :store_id AND referral_code = :referral_code LIMIT 1');

    $insertClick = $pdo->prepare('INSERT IGNORE INTO clicks (store_id, ambassador_id, referral_code, source, ip_hash, user_agent, created_at) VALUES (:store_id, :ambassador_id, :referral_code, :source, :ip_hash, :user_agent, NOW())');
    $insertOrder = $pdo->prepare('INSERT INTO orders (store_id, ambassador_id, referral_code, platform_order_id, customer_name, customer_email, amount, commission_amount, payout_status, created_at) VALUES (:store_id, :ambassador_id, :referral_code, :platform_order_id, :customer_name, :customer_email, :amount, :commission_amount, :payout_status, NOW()) ON DUPLICATE KEY UPDATE amount = VALUES(amount), commission_amount = VALUES(commission_amount), ambassador_id = VALUES(ambassador_id), referral_code = VALUES(referral_code)');
    $insertPayout = $pdo->prepare('INSERT IGNORE INTO payouts (store_id, ambassador_id, amount, status, invoice_url, created_at, paid_at) VALUES (:store_id, :ambassador_id, :amount, :status, :invoice_url, NOW(), NULL)');

    foreach ($storeDefs as $index => $storeDef) {
        $executeSeed($insertStore, [
            'name' => $storeDef['name'],
            'domain' => $storeDef['domain'],
            'platform' => $storeDef['platform'],
            'default_commission_percent' => $storeDef['default_commission_percent'],
            'status' => 'active',
        ], 'stores', 'INSERT');

        $selectStoreId->execute(['domain' => $storeDef['domain']]);
        $store = $selectStoreId->fetch();
        $storeId = (int)($store['id'] ?? 0);
        if ($storeId <= 0) {
            throw new RuntimeException('store_insert_failed');
        }

        $executeSeed($insertUser, ['email' => $storeDef['admin_email'], 'password_hash' => $demoPasswordHash, 'role' => 'store_admin', 'store_id' => $storeId, 'ambassador_id' => null], 'users', 'INSERT');
        $selectUser->execute(['email' => $storeDef['admin_email']]);
        $storeAdmin = $selectUser->fetch();
        if ($storeAdmin) {
            $updateStoreOwner->execute(['owner_user_id' => (int)$storeAdmin['id'], 'id' => $storeId]);
        }

        for ($i = 1; $i <= 3; $i++) {
            $ambEmail = ($index === 0 && $i === 1) ? 'amb1@trustai.no' : sprintf('ambassador-%d-%d@trustai.local', $index + 1, $i);
            $refCode = sprintf('s%da%dref', $storeId, $i);
            $executeSeed($insertUser, ['email' => $ambEmail, 'password_hash' => $demoPasswordHash, 'role' => 'ambassador', 'store_id' => $storeId, 'ambassador_id' => null], 'users', 'INSERT');
            $selectUser->execute(['email' => $ambEmail]);
            $ambUser = $selectUser->fetch();

            $commissionPercent = (float)$storeDef['default_commission_percent'] + $i;
            $executeSeed($insertAmb, [
                'store_id' => $storeId,
                'user_id' => $ambUser ? (int)$ambUser['id'] : null,
                'name' => sprintf('Demo Ambassador %d-%d', $index + 1, $i),
                'email' => $ambEmail,
                'phone' => '+4700000' . $index . $i,
                'referral_code' => $refCode,
                'status' => $i === 3 ? 'pending' : 'approved',
                'commission_percent' => $commissionPercent,
                'approved_at' => $i === 3 ? null : date('Y-m-d H:i:s'),
            ], 'ambassadors', 'INSERT');

            $selectAmb->execute(['store_id' => $storeId, 'referral_code' => $refCode]);
            $ambassador = $selectAmb->fetch();
            $ambassadorId = (int)($ambassador['id'] ?? 0);
            if ($ambassadorId <= 0) {
                throw new RuntimeException('ambassador_insert_failed');
            }

            if ($ambUser) {
                $pdo->prepare('UPDATE users SET ambassador_id = :ambassador_id WHERE id = :id')->execute(['ambassador_id' => $ambassadorId, 'id' => (int)$ambUser['id']]);
            }

            for ($c = 0; $c < 2; $c++) {
                $executeSeed($insertClick, [
                    'store_id' => $storeId,
                    'ambassador_id' => $ambassadorId,
                    'referral_code' => $refCode,
                    'source' => 'seed_demo_data',
                    'ip_hash' => hash('sha256', $ambEmail . '-click-' . $c),
                    'user_agent' => 'seed-script',
                ], 'clicks', 'INSERT');
            }

            if ($i !== 3) {
                for ($o = 1; $o <= 2; $o++) {
                    $amount = 400 + ($o * 75) + ($i * 25);
                    $commissionAmount = round($amount * ($commissionPercent / 100), 2);
                    $executeSeed($insertOrder, [
                        'store_id' => $storeId,
                        'ambassador_id' => $ambassadorId,
                        'referral_code' => $refCode,
                        'platform_order_id' => sprintf('seed-%d-%d-%d', $storeId, $i, $o),
                        'customer_name' => 'Demo Customer',
                        'customer_email' => sprintf('customer-%d-%d-%d@example.com', $storeId, $i, $o),
                        'amount' => $amount,
                        'commission_amount' => $commissionAmount,
                        'payout_status' => 'requested',
                    ], 'orders', 'INSERT');
                }

                $executeSeed($insertPayout, [
                    'store_id' => $storeId,
                    'ambassador_id' => $ambassadorId,
                    'amount' => 150 + ($i * 20),
                    'status' => 'requested',
                    'invoice_url' => null,
                ], 'payouts', 'INSERT');
            }
        }
    }

    $pdo->commit();
    $sendJson(200, ['ok' => true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $seedError = $e instanceof SeedSqlException ? $e : ($e->getPrevious() instanceof SeedSqlException ? $e->getPrevious() : null);
    if ($seedError instanceof SeedSqlException) {
        $sendJson(500, [
            'ok' => false,
            'error' => 'seed_failed',
            'table' => $seedError->tableName,
            'operation' => $seedError->operation,
            'message' => $seedError->getMessage(),
        ]);
    }

    $sendJson(500, [
        'ok' => false,
        'error' => 'seed_failed',
        'table' => 'unknown',
        'message' => $e->getMessage() !== '' ? $e->getMessage() : 'seed_failed',
    ]);
}
