<?php
declare(strict_types=1);

if (!function_exists('trustaiGetEnv')) {
    function trustaiGetEnv(string $key, string $default = ''): string
    {
        $env = getenv($key);
        if ($env !== false && $env !== '') return (string)$env;
        if (defined($key)) return (string)constant($key);
        return $default;
    }
}

/**
 * Kaller Anthropic Claude API.
 * Returnerer ['ok' => bool, 'text' => string, 'error' => string|null]
 */
function trustaiCallClaude(string $systemPrompt, array $messages, int $maxTokens = 1024): array
{
    $apiKey = trustaiGetEnv('ANTHROPIC_API_KEY');
    if ($apiKey === '') {
        return ['ok' => false, 'error' => 'api_key_missing', 'text' => ''];
    }

    $body = [
        'model' => 'claude-sonnet-4-5',
        'max_tokens' => $maxTokens,
        'system' => $systemPrompt,
        'messages' => $messages,
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_TIMEOUT => 60,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'error' => 'curl_error: ' . $err, 'text' => ''];
    }

    $data = json_decode($response, true);
    if ($httpCode !== 200 || !is_array($data)) {
        $errMsg = is_array($data) && isset($data['error']['message']) ? $data['error']['message'] : ('http_' . $httpCode);
        error_log('Anthropic API error: ' . $errMsg . ' | ' . substr((string)$response, 0, 500));
        return ['ok' => false, 'error' => $errMsg, 'text' => ''];
    }

    $text = '';
    foreach (($data['content'] ?? []) as $block) {
        if (($block['type'] ?? '') === 'text') {
            $text .= (string)($block['text'] ?? '');
        }
    }

    return ['ok' => true, 'text' => trim($text), 'error' => null];
}

/**
 * Bygger kontekst-streng fra dashboard-data for AI.
 */
function trustaiBuildContext(string $role, array $data): string
{
    $lines = [];
    $lines[] = 'Bruker-rolle: ' . $role;
    $lines[] = 'Dato: ' . date('Y-m-d H:i');

    if (!empty($data['ambassador'])) {
        $a = $data['ambassador'];
        $lines[] = "Ambassadør: {$a['name']} (status: {$a['status']}, kommisjon: {$a['commission_percent']}%)";
    }
    if (!empty($data['store'])) {
        $s = $data['store'];
        $lines[] = "Butikk: {$s['name']} ({$s['domain']})";
    }
    if (!empty($data['metrics'])) {
        $m = $data['metrics'];
        $lines[] = 'Metrics: ' . json_encode($m, JSON_UNESCAPED_UNICODE);
    }
    if (!empty($data['orders'])) {
        $count = count($data['orders']);
        $sum = array_sum(array_column($data['orders'], 'amount'));
        $lines[] = "Ordrer: $count totalt, sum " . number_format($sum, 2) . " kr";
    }
    if (!empty($data['clicks'])) {
        $count = count($data['clicks']);
        $lines[] = "Klikk: $count totalt";
    }
    if (!empty($data['ambassadors'])) {
        $lines[] = 'Ambassadører i data: ' . count($data['ambassadors']);
    }

    return implode("\n", $lines);
}

/**
 * Returnerer system-prompt tilpasset rolle.
 */
function trustaiSystemPromptForRole(string $role): string
{
    $base = "Du er TrustAI Coach — en proaktiv salgs- og prestasjonsrådgiver bygget inn i TrustAI-plattformen. " .
            "TrustAI er et referral/ambassadør-system. Du svarer på norsk, kort, konkret og handlingsrettet. " .
            "Når du gir tall, vær presis. Når du foreslår handlinger, prioriter de som gir mest effekt først.";

    switch ($role) {
        case 'ambassador':
            return $base . "\n\nFOKUS: Hjelp ambassadøren tjene mer. Foreslå konkrete delingstekster, beste tidspunkter, hvilke kanaler å bruke (basert på data), og motivasjon. Vær konkret som en sales coach.";
        case 'store_admin':
            return $base . "\n\nFOKUS: Hjelp butikk-eier optimalisere ambassadør-programmet. Identifiser stille ambassadører, anbefal commission-justeringer, foreslå hvem å rekruttere, og estimer trender. Vær strategisk som en business advisor.";
        case 'super_admin':
            return $base . "\n\nFOKUS: Plattform-helse, anomalier, vekst-muligheter. Tenk som en SaaS-driftsoperatør.";
        default:
            return $base;
    }
}
