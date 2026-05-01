<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

$user = requireLogin();
if (!in_array($user['role'], ['super_admin', 'store_admin'], true)) {
    jsonResponse(403, ['ok' => false, 'error' => 'forbidden_role']);
}

if ($user['role'] === 'store_admin') {
    $stmt = $pdo->prepare('SELECT id AS application_id, name, email, phone, store_id, status, created_at, approved_at FROM ambassadors WHERE store_id = :store_id ORDER BY created_at DESC');
    $stmt->execute(['store_id' => (int)$user['store_id']]);
} else {
    $stmt = $pdo->query('SELECT id AS application_id, name, email, phone, store_id, status, created_at, approved_at FROM ambassadors ORDER BY created_at DESC');
}

jsonResponse(200, ['ok' => true, 'applications' => $stmt->fetchAll()]);
