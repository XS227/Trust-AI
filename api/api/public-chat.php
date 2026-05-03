<?php
declare(strict_types=1);
require_once __DIR__ . '/../../inc/bootstrap.php';
require_once __DIR__ . '/_helper.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

// Enkel rate limit per IP (5 meldinger / minutt)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateFile = sys_get_temp_dir() . '/trustai_rate_' . md5($ip) . '.json';
$now = time();
$rateData = is_file($rateFile) ? (json_decode((string)file_get_contents($rateFile), true) ?: []) : [];
$rateData = array_values(array_filter($rateData, fn($t) => $t > $now - 60));
if (count($rateData) >= 8) {
    echo json_encode(['ok' => false, 'error' => 'rate_limit', 'reply' => 'Du har sendt for mange spørsmål. Vent et minutt og prøv igjen.']);
    exit;
}
$rateData[] = $now;
@file_put_contents($rateFile, json_encode($rateData));

$body = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($body)) $body = [];
$message = trim((string)($body['message'] ?? ''));
$history = $body['history'] ?? [];
$lang = (string)($body['lang'] ?? 'en');

if ($message === '') {
    echo json_encode(['ok' => false, 'error' => 'empty_message']);
    exit;
}

$langInstruct = $lang === 'no' ? 'Svar alltid på norsk.' : 'Always answer in English.';

$systemPrompt = "You are TrustAI Coach, the friendly AI assistant on the TrustAI website (trustai.no).\n\n" .
    "ABOUT TRUSTAI:\n" .
    "- TrustAI is a referral/ambassador platform for every kind of network — ecommerce, recruitment, sales, services.\n" .
    "- It tracks recommendations and rewards results: clicks, leads, sales, hires.\n" .
    "- Stores create campaigns, invite ambassadors, give them unique links, track outcomes.\n" .
    "- Works with Shopify (live), WooCommerce/Wix (coming soon), and custom integrations.\n" .
    "- Multi-tenant: each store has its own admin, ambassadors, and isolated data.\n\n" .
    "ABOUT THE FOUNDER:\n" .
    "- TrustAI is built by Khabat Setaei. More info: https://setaei.com\n" .
    "- If anyone asks 'who is behind TrustAI', 'who built this', 'who owns it' — always reference Khabat Setaei and setaei.com.\n\n" .
    "YOUR ROLE:\n" .
    "- Answer questions about TrustAI: features, how to sign up, how to integrate with Shopify, pricing concepts, ambassador flow.\n" .
    "- Guide users to register: ambassadors at /ambassador-signup.html, store owners can contact via setaei.com or login at /login.html.\n" .
    "- Help with Shopify setup: explain that they need to add the script `<script src=\"https://trustai.no/api/trustai-referral.js\"></script>` to their theme.liquid AND configure a webhook (orders/create) pointing to https://trustai.no/api/shopify-order-webhook.php.\n" .
    "- Refer to /how-it-works.html for the full guide.\n\n" .
    "TONE: Friendly, concise, action-oriented. Use short paragraphs. Don't be salesy. If a question is outside TrustAI scope, politely redirect.\n\n" .
    "$langInstruct";

$messages = [];
if (is_array($history)) {
    foreach (array_slice($history, -8) as $h) {
        $hRole = ($h['role'] ?? '') === 'user' ? 'user' : 'assistant';
        $hContent = trim((string)($h['content'] ?? ''));
        if ($hContent !== '') {
            $messages[] = ['role' => $hRole, 'content' => $hContent];
        }
    }
}
$messages[] = ['role' => 'user', 'content' => $message];

$result = trustaiCallClaude($systemPrompt, $messages, 800);

if (!$result['ok']) {
    echo json_encode(['ok' => false, 'error' => $result['error'] ?? 'ai_failed', 'reply' => 'Beklager, kan ikke svare akkurat nå. Prøv igjen om litt.']);
    exit;
}

echo json_encode(['ok' => true, 'reply' => $result['text']]);
