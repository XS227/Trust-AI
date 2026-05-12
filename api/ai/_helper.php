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
 * True iff a valid Anthropic API key is configured.
 */
function trustaiAiIsEnabled(): bool
{
    return trustaiGetEnv('ANTHROPIC_API_KEY') !== '';
}

/**
 * Demo chat reply — matched by keyword, never exposes API errors to the caller.
 */
function trustaiDemoChat(string $message, string $role = 'ambassador'): string
{
    $m = mb_strtolower($message, 'UTF-8');

    if ($role === 'ambassador') {
        if (preg_match('/kanal|instagram|facebook|linkedin|tiktok|snapchat|social/', $m)) {
            return "📊 Basert på bransjedata konverterer personlig melding (DM) best — typisk 3–8 % konvertering mot 0,5–1 % for åpne innlegg.\n\nPrioritér:\n1️⃣ Direkte melding til folk du kjenner\n2️⃣ Instagram Stories med lenke\n3️⃣ LinkedIn hvis produktet passer B2B\n\nTest én kanal av gangen i 2 uker for å se hva som fungerer for deg.";
        }
        if (preg_match('/tekst|script|melding|skrive|hva sier|hva skriver|eksempel/', $m)) {
            return "✍️ Her er en delingstekst som fungerer godt:\n\n\"Hei [navn]! Jeg har brukt [produkt] en stund og er veldig fornøyd. Tenkte du kanskje ville prøve — her er min personlige lenke: [din lenke] 🙂\"\n\nTips: Personlige meldinger konverterer 4× bedre enn generiske poster. Nevn alltid noe spesifikt du liker med produktet.";
        }
        if (preg_match('/tid|tidspunkt|når|morgen|kveld|dag|uke|posting time/', $m)) {
            return "🕐 Beste tidspunkter for deling (norsk publikum):\n\n▸ Tirsdag–torsdag 18:00–21:00\n▸ Lunsjpause 11:30–13:00 (høy åpningsrate)\n▸ Unngå fredag kveld og mandag morgen\n\nFor DM: send tirsdager kl. 19–20 — folk er avslappet og mer åpne for å klikke.";
        }
        if (preg_match('/provisjon|tjene|mer penger|inntekt|bonus|commission|reward/', $m)) {
            return "💰 For å øke inntjeningen:\n\n1. Del med 5 nye kontakter per uke — selv 2 % konvertering gir jevn inntekt\n2. Send en påminnelse etter 3 dager til de som klikket men ikke kjøpte\n3. Del rundt lønnsdato (25.–28.) — folk handler mer da\n4. Post en personlig anmeldelse — øker konvertering med opptil 40 %";
        }
        if (preg_match('/klikk|trafikk|lenke|link|besøk|click/', $m)) {
            return "🔗 Tips for å øke klikk:\n\n• Legg lenken i bio + del i Stories med «lenke i bio»\n• Bruk en URL-shortener med din merkevare\n• Klikk uten konvertering betyr folk er interesserte men ikke overbevist — test et sterkere call-to-action.";
        }
        if (preg_match('/tips|råd|hjelp|forbedre|bedre|optimali|hva bør/', $m)) {
            return "🎯 Topp 5 råd for ambassadører:\n\n1. Del til folk du kjenner — ikke ukjente i grupper\n2. Fortell din egen historie med produktet\n3. Del 2–3 ganger i uken — ikke daglig (oppleves som spam)\n4. Svar raskt på spørsmål fra de som klikker\n5. Noter hvilke meldinger gir flest klikk og gjør mer av det\n\nDe beste ambassadørene tjener 3–10× mer enn snittet ved å følge disse prinsippene.";
        }
        if (preg_match('/aktiv|activity|statistikk|status|oversikt|dashboard/', $m)) {
            return "📈 Slik leser du din aktivitetsstatus:\n\n• Klikk = antall ganger noen klikket på din referral-lenke\n• Konvertering = klikk som endte med et salg\n• Provisjon = din andel av salgsverdien\n\nMålet er å øke konverteringsraten din over 5 %. Fokuser på kvalitet fremfor kvantitet i hvem du deler med.";
        }
        return "🤖 AI Coach er snart klar med personlige anbefalinger basert på dine klikk, salg og konverteringer.\n\nI mellomtiden: del referral-lenken din med 3–5 nye kontakter denne uken. Selv en konverteringsrate på 2 % gir stabil inntekt over tid.";
    }

    if ($role === 'store_admin') {
        if (preg_match('/stille|inaktiv|ikke delt|hvem jobber|ambassadør aktivitet/', $m)) {
            return "📊 Slik aktiverer du stille ambassadører:\n\n1. Identifiser ambassadører uten klikk siste 14 dager\n2. Send en personlig e-post — ikke masseutsendelse\n3. Gi dem nytt materiale: produktbilder, tekstforslag, rabattkode\n4. Sett et mål: «Del med 3 venner denne uken — vi dobler provisjonen din»\n\nAmbassadører uten aktivitet etter 30 dager bør erstattes med mer motiverte kandidater.";
        }
        if (preg_match('/provisjon|commission|prosent|reward|belønning|øke salg/', $m)) {
            return "💡 Provisjons-optimalisering:\n\n▸ Under 8 %: Vurder å øke — ambassadørene mister motivasjon\n▸ 8–15 %: Sweet spot for de fleste bransjer\n▸ Over 20 %: Kan fungere, men pass på marginen\n\nBonusstrategi: gi 2× provisjon for de første 5 salgene til nye ambassadører — det skaper momentum og øker sjansen for at de fortsetter å dele.";
        }
        if (preg_match('/konverter|salg|trakt|funnel|kanal|landingsside/', $m)) {
            return "📈 For å øke konverteringer:\n\n1. Sjekk at landingssiden er rask og mobiloptimert\n2. Del profesjonelle produktbilder ambassadørene kan bruke\n3. Gi ambassadørene en unik rabattkode som sporer salg\n4. Vis kundeanmeldelser tydelig på landingssiden\n\nEn forbedring fra 1 % til 2 % konverteringsrate dobler inntekten uten ekstra ambassadører.";
        }
        if (preg_match('/rekrutter|nye ambassadør|finne|søk|apply|invite/', $m)) {
            return "🎯 Slik rekrutterer du sterke ambassadører:\n\n1. Eksisterende kunder — de som allerede elsker produktet\n2. Micro-influencere (1k–10k følgere) — høyere engasjement\n3. Ansatte og partnere — ofte oversett men svært effektive\n4. Studenter innen markedsføring — motiverte og digitale\n\nUnngå å rekruttere folk som bare vil ha provisjon uten å tro på produktet — de slutter raskt.";
        }
        if (preg_match('/statistikk|data|rapport|oversikt|analyse|dashboard/', $m)) {
            return "📊 Nøkkeltall å følge med på:\n\n• Klikk/uke per ambassadør — indikerer aktivitet\n• Konverteringsrate — klikkvalitet\n• Provisjon opptjent vs utbetalt — likviditetsstyring\n• Tid fra klikk til kjøp — konverteringssyklus\n\nEn god ambassadør har >3 % konverteringsrate. Under 1 % betyr enten feil publikum eller svak landingsside.";
        }
        return "🤖 AI Coach er snart klar med detaljerte analyser av ambassadørprogrammet ditt.\n\nDet viktigste du kan gjøre nå: kontakt de 3 ambassadørene med flest klikk men lavest salg — de er interesserte men trenger bedre verktøy eller tekster for å lukke salg.";
    }

    // super_admin / generic
    return "🤖 AI Coach er ikke aktivert ennå i denne demoen.\n\nSnart får du personlige anbefalinger basert på klikk, salg og konverteringer på tvers av alle butikker og ambassadører.";
}

/**
 * Demo insights cards — role-specific, no AI call needed.
 */
function trustaiDemoInsights(string $role): array
{
    if ($role === 'ambassador') {
        return [
            ['icon' => '📊', 'title' => 'Klikk-trend', 'message' => 'Konverteringsraten din er 14 % — langt over snittet på 3 %. Du gjør noe riktig! Del lenken med 5 nye kontakter denne uken for å skalere inntekten.', 'priority' => 'high', 'action' => 'Del til 5 nye kontakter i dag'],
            ['icon' => '🕐', 'title' => 'Beste delingstidspunkt', 'message' => 'Norske brukere klikker mest tirsdag–torsdag 18–21. Din neste deling bør treffe denne tidsperioden for maksimal effekt.', 'priority' => 'medium', 'action' => 'Planlegg neste innlegg til torsdag kl. 19:00'],
            ['icon' => '💬', 'title' => 'Personlig melding vinner', 'message' => 'Direkte meldinger konverterer 4× bedre enn åpne innlegg. Bruk Stories og DM fremfor kun feed-poster.', 'priority' => 'medium', 'action' => 'Send DM til 3 venner med din personlige anbefaling i dag'],
            ['icon' => '💰', 'title' => 'Estimert opptjening', 'message' => 'Med nåværende aktivitet er du på vei mot ca. 220 kr provisjon denne måneden. Med 2× aktivitet kan du nå 440 kr.', 'priority' => 'low', 'action' => 'Øk delingsfrekvensen til 3 ganger i uken'],
        ];
    }
    if ($role === 'store_admin') {
        return [
            ['icon' => '🤫', 'title' => 'Stille ambassadører', 'message' => 'Noen ambassadører har ikke delt på over 14 dager. Disse har høyt potensial — de meldte seg på frivillig. En kort personlig e-post kan reaktivere dem.', 'priority' => 'high', 'action' => 'Send motiverende e-post med nye delingstekster'],
            ['icon' => '📈', 'title' => 'Konverteringsmulighet', 'message' => 'Klikk→salg-raten din er 14 % — godt over bransjesnittet på 3–5 %. Øk antall delinger for å utnytte dette fullt ut.', 'priority' => 'high', 'action' => 'Rekrutter 3–5 nye ambassadører denne måneden'],
            ['icon' => '💡', 'title' => 'Provisjons-boost', 'message' => '10 % provisjon er konkurransedyktig. Vurder 15 % for de første 5 salgene til nye ambassadører — det skaper momentum og reduserer frafall.', 'priority' => 'medium', 'action' => 'Aktiver velkomst-bonus for nye ambassadører'],
            ['icon' => '🎯', 'title' => 'Topp-ambassadør', 'message' => 'Én ambassadør driver størstedelen av konverteringene. Sørg for at vedkommende har alt — produktbilder, oppdaterte tekster, eksklusiv rabattkode.', 'priority' => 'medium', 'action' => 'Ta kontakt med din beste ambassadør og spør om de trenger noe'],
        ];
    }
    // super_admin / default
    return [
        ['icon' => '🏪', 'title' => 'Plattform-helse', 'message' => 'Demoplattformen kjører stabilt med aktive ambassadører og testdata på plass. Alt er klart for onboarding av reelle butikker.', 'priority' => 'medium', 'action' => 'Inviter første reelle butikk til å teste onboarding-flyten'],
        ['icon' => '📊', 'title' => 'Vekstpotensial', 'message' => 'Med ambassador-program live er neste steg å dokumentere onboarding-opplevelsen og justere basert på brukerfeedback.', 'priority' => 'low', 'action' => 'Gjennomfør brukertest av onboarding-wizard'],
        ['icon' => '🔒', 'title' => 'Sikkerhet OK', 'message' => 'Admin-kontoer bruker passord-auth. Vipps-innlogging er konfigurert og aktiv for ambassadører og butikker.', 'priority' => 'low', 'action' => 'Test Vipps-innlogging med en reell konto'],
    ];
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
