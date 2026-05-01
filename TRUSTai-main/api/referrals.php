<?php
require __DIR__ . '/../inc/db.php';
header('Content-Type: application/json; charset=utf-8');

$ref = $_COOKIE['trustai_ref'] ?? '';
$status = $_GET['status'] ?? 'inviter';

if (!in_array($status, ['inviter','venter','vervede'], true)) $status = 'inviter';

if (!preg_match('/^[A-Za-z0-9_-]{3,32}$/', $ref)) {
  echo json_encode(['ok'=>true,'rows'=>[]]);
  exit;
}

$stmt = $pdo->prepare("SELECT username, registered_at, bonus_ore, contact
                       FROM referrals WHERE ref_code=? AND status=?
                       ORDER BY created_at DESC LIMIT 200");
$stmt->execute([$ref, $status]);

$rows = [];
while ($r = $stmt->fetch()) {
  $rows[] = [
    'user' => $r['username'] ?? '—',
    'date' => $r['registered_at'] ? date('Y-m-d', strtotime($r['registered_at'])) : '—',
    'bonus' => number_format(((int)$r['bonus_ore'])/100, 0, ',', ' ') . ' kr',
    'contact' => $r['contact'] ?? '—',
  ];
}

echo json_encode(['ok'=>true,'rows'=>$rows]);
