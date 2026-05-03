<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';
require_once __DIR__ . '/_helper.php';

$user = getCurrentUser();
if (!$user) {
    jsonResponse(401, ['ok' => false, 'error' => 'unauthorized']);
}

$role = (string)($user['role'] ?? '');
$userId = (int)($user['id'] ?? 0);
$cacheFile = sys_get_temp_dir() . '/trustai_ai_insights_' . $userId . '.json';
$cacheMaxAge = 3600; // 1 time

// Force refresh hvis ?refresh=1
$forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';

if (!$forceRefresh && is_file($cacheFile) && (time() - filemtime($cacheFile) < $cacheMaxAge)) {
    header('Content-Type: application/json; charset=utf-8');
    echo file_get_contents($cacheFile);
    exit;
}

// Bygg datakontekst
$context = '';
try {
    if ($role === 'ambassador' && !empty($user['ambassador_id'])) {
        $ambId = (int)$user['ambassador_id'];
        $a = $pdo->prepare('SELECT name, status, commission_percent FROM ambassadors WHERE id = :id LIMIT 1');
        $a->execute(['id' => $ambId]);
        $amb = $a->fetch(PDO::FETCH_ASSOC) ?: [];
        $clicks = (int)$pdo->query("SELECT COUNT(*) FROM clicks WHERE ambassador_id = $ambId")->fetchColumn();
        $clicksByDay = $pdo->query("SELECT DATE(created_at) as d, COUNT(*) as c FROM clicks WHERE ambassador_id = $ambId GROUP BY DATE(created_at) ORDER BY d DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
        $orders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE ambassador_id = $ambId")->fetchColumn();
        $sales = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE ambassador_id = $ambId")->fetchColumn();
        $earned = (float)$pdo->query("SELECT COALESCE(SUM(commission_amount),0) FROM orders WHERE ambassador_id = $ambId")->fetchColumn();
        $unpaid = (float)$pdo->query("SELECT COALESCE(SUM(commission_amount),0) FROM orders WHERE ambassador_id = $ambId AND payout_status IN ('requested','pending')")->fetchColumn();

        $context = "Ambassadør: {$amb['name']}, status: {$amb['status']}, kommisjon: {$amb['commission_percent']}%\n" .
                   "Totalt: $clicks klikk, $orders salg, " . number_format($sales, 0) . " kr i salg, " . number_format($earned, 0) . " kr provisjon (ubetalt: " . number_format($unpaid, 0) . ")\n" .
                   "Konvertering: " . ($clicks > 0 ? round($orders / $clicks * 100, 1) : 0) . "%\n" .
                   "Klikk siste 7 dager: " . json_encode($clicksByDay, JSON_UNESCAPED_UNICODE);
    } elseif ($role === 'store_admin' && !empty($user['store_id'])) {
        $storeId = (int)$user['store_id'];
        $store = $pdo->prepare('SELECT name, domain FROM stores WHERE id = :id LIMIT 1');
        $store->execute(['id' => $storeId]);
        $s = $store->fetch(PDO::FETCH_ASSOC) ?: [];
        $ambStats = $pdo->query("SELECT a.id, a.name, a.status,
            (SELECT COUNT(*) FROM clicks c WHERE c.ambassador_id = a.id) as clicks,
            (SELECT COUNT(*) FROM orders o WHERE o.ambassador_id = a.id) as orders,
            (SELECT COALESCE(SUM(o.amount),0) FROM orders o WHERE o.ambassador_id = a.id) as sales
            FROM ambassadors a WHERE a.store_id = $storeId")->fetchAll(PDO::FETCH_ASSOC);
        $totalSales = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE store_id = $storeId")->fetchColumn();
        $pending = (int)$pdo->query("SELECT COUNT(*) FROM ambassadors WHERE store_id = $storeId AND status = 'pending'")->fetchColumn();

        $context = "Butikk: {$s['name']} ({$s['domain']})\n" .
                   "Totalt salg: " . number_format($totalSales, 0) . " kr\n" .
                   "Pending søknader: $pending\n" .
                   "Ambassadører: " . json_encode($ambStats, JSON_UNESCAPED_UNICODE);
    } else {
        $stores = (int)$pdo->query("SELECT COUNT(*) FROM stores WHERE status = 'active'")->fetchColumn();
        $amb = (int)$pdo->query("SELECT COUNT(*) FROM ambassadors WHERE status = 'approved'")->fetchColumn();
        $pending = (int)$pdo->query("SELECT COUNT(*) FROM ambassadors WHERE status = 'pending'")->fetchColumn();
        $sales = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM orders")->fetchColumn();
        $context = "Plattform: $stores aktive butikker, $amb godkjente ambassadører, $pending pending søknader, " . number_format($sales, 0) . " kr totalt salg.";
    }
} catch (Throwable $e) {
    error_log('insights context build failed: ' . $e->getMessage());
}

$systemPrompt = trustaiSystemPromptForRole($role) .
    "\n\nFORMAT: Returner BARE et JSON-array med 3-5 insikter. Hver insikt har felter: " .
    '{"icon":"🎯","title":"Kort tittel","message":"Detaljert anbefaling","priority":"high|medium|low","action":"Foreslått handling (kort)"}. ' .
    "Ingen markdown-fences, ingen forklaring rundt JSONen, KUN ren JSON.";

$messages = [['role' => 'user', 'content' => "Basert på følgende data, gi meg 3-5 proaktive insikter med konkrete anbefalinger:\n\n" . $context]];

$result = trustaiCallClaude($systemPrompt, $messages, 1500);

if (!$result['ok']) {
    jsonResponse(500, ['ok' => false, 'error' => $result['error'] ?? 'ai_failed', 'insights' => []]);
}

// Parse JSON-respons
$text = $result['text'];
$text = preg_replace('/^```(?:json)?\s*/i', '', $text);
$text = preg_replace('/\s*```$/', '', $text);
$insights = json_decode($text, true);

if (!is_array($insights)) {
    // Fallback hvis Claude ga tekst i stedet for JSON
    $insights = [['icon' => '💡', 'title' => 'Innsikt', 'message' => $result['text'], 'priority' => 'medium', 'action' => '']];
}

$payload = json_encode([
    'ok' => true,
    'insights' => $insights,
    'role' => $role,
    'updated_at' => date('c'),
]);

@file_put_contents($cacheFile, $payload);
header('Content-Type: application/json; charset=utf-8');
echo $payload;
