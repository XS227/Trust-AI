<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';
require_once __DIR__ . '/_helper.php';

$user = getCurrentUser();
if (!$user) {
    jsonResponse(401, ['ok' => false, 'error' => 'unauthorized']);
}

$body = readJsonBody();
$message = trim((string)($body['message'] ?? ''));
$history = $body['history'] ?? [];
if ($message === '') {
    jsonResponse(400, ['ok' => false, 'error' => 'empty_message']);
}

$role = (string)($user['role'] ?? 'ambassador');
$systemPrompt = trustaiSystemPromptForRole($role);

// Hent kontekst basert på rolle
$context = '';
try {
    if ($role === 'ambassador' && !empty($user['ambassador_id'])) {
        $ambId = (int)$user['ambassador_id'];
        $storeId = (int)($user['store_id'] ?? 0);
        $a = $pdo->prepare('SELECT name, status, commission_percent, referral_code FROM ambassadors WHERE id = :id LIMIT 1');
        $a->execute(['id' => $ambId]);
        $amb = $a->fetch(PDO::FETCH_ASSOC) ?: [];
        $clicks = (int)$pdo->query("SELECT COUNT(*) FROM clicks WHERE ambassador_id = $ambId")->fetchColumn();
        $orders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE ambassador_id = $ambId")->fetchColumn();
        $sales = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE ambassador_id = $ambId")->fetchColumn();
        $earned = (float)$pdo->query("SELECT COALESCE(SUM(commission_amount),0) FROM orders WHERE ambassador_id = $ambId")->fetchColumn();
        $context = trustaiBuildContext($role, [
            'ambassador' => $amb,
            'metrics' => [
                'clicks' => $clicks,
                'orders' => $orders,
                'total_sales' => $sales,
                'total_commission' => $earned,
                'conversion_rate' => $clicks > 0 ? round(($orders / $clicks) * 100, 1) : 0,
            ],
        ]);
    } elseif ($role === 'store_admin' && !empty($user['store_id'])) {
        $storeId = (int)$user['store_id'];
        $store = $pdo->prepare('SELECT name, domain FROM stores WHERE id = :id LIMIT 1');
        $store->execute(['id' => $storeId]);
        $s = $store->fetch(PDO::FETCH_ASSOC) ?: [];
        $totalAmb = (int)$pdo->query("SELECT COUNT(*) FROM ambassadors WHERE store_id = $storeId")->fetchColumn();
        $approvedAmb = (int)$pdo->query("SELECT COUNT(*) FROM ambassadors WHERE store_id = $storeId AND status = 'approved'")->fetchColumn();
        $pendingAmb = (int)$pdo->query("SELECT COUNT(*) FROM ambassadors WHERE store_id = $storeId AND status = 'pending'")->fetchColumn();
        $sales = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE store_id = $storeId")->fetchColumn();
        $context = trustaiBuildContext($role, [
            'store' => $s,
            'metrics' => [
                'ambassadors_total' => $totalAmb,
                'ambassadors_approved' => $approvedAmb,
                'ambassadors_pending' => $pendingAmb,
                'total_sales' => $sales,
            ],
        ]);
    } else {
        $stores = (int)$pdo->query("SELECT COUNT(*) FROM stores WHERE status = 'active'")->fetchColumn();
        $amb = (int)$pdo->query("SELECT COUNT(*) FROM ambassadors WHERE status = 'approved'")->fetchColumn();
        $sales = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM orders")->fetchColumn();
        $context = trustaiBuildContext($role, [
            'metrics' => [
                'stores' => $stores,
                'ambassadors' => $amb,
                'total_sales' => $sales,
            ],
        ]);
    }
} catch (Throwable $e) {
    error_log('ai/chat context build failed: ' . $e->getMessage());
}

// Bygg meldinger
$messages = [];
if (is_array($history)) {
    foreach ($history as $h) {
        $hRole = ($h['role'] ?? '') === 'user' ? 'user' : 'assistant';
        $hContent = trim((string)($h['content'] ?? ''));
        if ($hContent !== '') {
            $messages[] = ['role' => $hRole, 'content' => $hContent];
        }
    }
}

$userTurn = $message;
if ($context !== '') {
    $userTurn = "[Kontekst om brukerens data:\n" . $context . "]\n\nSpørsmål: " . $message;
}
$messages[] = ['role' => 'user', 'content' => $userTurn];

$result = trustaiCallClaude($systemPrompt, $messages, 1024);

if (!$result['ok']) {
    jsonResponse(500, ['ok' => false, 'error' => $result['error'] ?? 'ai_failed']);
}

jsonResponse(200, ['ok' => true, 'reply' => $result['text']]);
