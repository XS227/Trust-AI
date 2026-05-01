<?php
header('Content-Type: application/json');

$configPath = __DIR__ . '/../inc/config.local.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
  $data = $_POST;
}

$action = $data['action'] ?? null;
$payload = $data['payload'] ?? $data;

function respond(int $status, array $payload): void
{
  http_response_code($status);
  echo json_encode($payload);
  exit;
}

function readDbPayload(array $payload): array
{
  $db = $payload['db'] ?? $payload;
  return [
    'host' => trim((string) ($db['host'] ?? '')),
    'name' => trim((string) ($db['name'] ?? '')),
    'user' => trim((string) ($db['user'] ?? '')),
    'pass' => (string) ($db['pass'] ?? ''),
  ];
}

function requireDbFields(array $db): void
{
  foreach (['host', 'name', 'user'] as $field) {
    if ($db[$field] === '') {
      respond(422, ['ok' => false, 'error' => 'Mangler databasefelt: ' . $field]);
    }
  }
}

function connectDb(array $db): PDO
{
  $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['name']);
  return new PDO($dsn, $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
}

if (file_exists($configPath)) {
  respond(409, ['ok' => false, 'error' => 'Allerede installert.']);
}

if ($action === 'test_db') {
  $db = readDbPayload((array) $payload);
  requireDbFields($db);
  try {
    connectDb($db);
    respond(200, ['ok' => true]);
  } catch (PDOException $e) {
    respond(400, ['ok' => false, 'error' => $e->getMessage()]);
  }
}

if ($action === 'create_schema') {
  $db = readDbPayload((array) ($payload['db'] ?? []));
  requireDbFields($db);
  $adminEmail = trim((string) ($payload['admin_email'] ?? ''));
  $adminPass = (string) ($payload['admin_pass'] ?? '');
  if ($adminEmail === '' || $adminPass === '') {
    respond(422, ['ok' => false, 'error' => 'Mangler admin e-post/passord']);
  }

  try {
    $pdo = connectDb($db);
    $pdo->exec("CREATE TABLE IF NOT EXISTS clicks (
      id INT AUTO_INCREMENT PRIMARY KEY,
      ref_code VARCHAR(32) NOT NULL,
      ip_hash CHAR(64) NOT NULL,
      ua_hash CHAR(64) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_ref_code (ref_code),
      INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS referrals (
      id INT AUTO_INCREMENT PRIMARY KEY,
      ref_code VARCHAR(32) NOT NULL,
      username VARCHAR(120) NOT NULL DEFAULT '',
      contact VARCHAR(255) NOT NULL DEFAULT '',
      status VARCHAR(24) NOT NULL DEFAULT 'inviter',
      registered_at DATETIME DEFAULT NULL,
      bonus_ore INT NOT NULL DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_ref_code (ref_code),
      INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      email VARCHAR(190) NOT NULL UNIQUE,
      password_hash VARCHAR(255) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $pdo->prepare('SELECT id FROM admin_users WHERE email = ?');
    $stmt->execute([$adminEmail]);
    if (!$stmt->fetchColumn()) {
      $hash = password_hash($adminPass, PASSWORD_DEFAULT);
      $insert = $pdo->prepare('INSERT INTO admin_users (email, password_hash) VALUES (?, ?)');
      $insert->execute([$adminEmail, $hash]);
    }

    respond(201, ['ok' => true]);
  } catch (PDOException $e) {
    respond(400, ['ok' => false, 'error' => $e->getMessage()]);
  }
}

if ($action === 'save_vipps_and_finalize') {
  $db = readDbPayload((array) ($payload['db'] ?? []));
  requireDbFields($db);
  $vipps = (array) ($payload['vipps'] ?? []);

  $config = [
    'db' => $db,
    'vipps' => [
      'env' => trim((string) ($vipps['env'] ?? 'test')),
      'merchantSerialNumber' => trim((string) ($vipps['merchantSerialNumber'] ?? '')),
      'client_id' => trim((string) ($vipps['client_id'] ?? '')),
      'client_secret' => trim((string) ($vipps['client_secret'] ?? '')),
      'subscription_key_primary' => trim((string) ($vipps['subscription_key_primary'] ?? '')),
      'issuer' => trim((string) ($vipps['issuer'] ?? '')),
      'well_known' => trim((string) ($vipps['well_known'] ?? '')),
      'authorization_endpoint' => trim((string) ($vipps['authorization_endpoint'] ?? '')),
      'token_endpoint' => trim((string) ($vipps['token_endpoint'] ?? '')),
      'userinfo_endpoint' => trim((string) ($vipps['userinfo_endpoint'] ?? '')),
      'scope' => trim((string) ($vipps['scope'] ?? 'openid name phone')),
      'redirect_uri' => trim((string) ($vipps['redirect_uri'] ?? 'https://trustai.no/auth/vipps/callback.php')),
      'post_login_redirect' => trim((string) ($vipps['post_login_redirect'] ?? '/?logged=1')),
    ],
    'installed_at' => gmdate('c'),
  ];

  $configContents = "<?php\nreturn " . var_export($config, true) . ";\n";

  if (file_put_contents($configPath, $configContents) === false) {
    respond(500, ['ok' => false, 'error' => 'Kunne ikke lagre config.local.php']);
  }

  respond(201, ['ok' => true, 'redirect' => '/']);
}

respond(400, ['ok' => false, 'error' => 'Ugyldig handling.']);
