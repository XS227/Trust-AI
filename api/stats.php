<?php
require __DIR__ . '/../inc/db.php';
header('Content-Type: application/json; charset=utf-8');

$ref = $_COOKIE['trustai_ref'] ?? '';
if (!preg_match('/^[A-Za-z0-9_-]{3,32}$/', $ref)) {
  echo json_encode(['ok'=>true,'clicks'=>0,'leads'=>0,'customers'=>0,'bonus_ore'=>0,'series'=>[]]);
  exit;
}

// siste 14 dager klikk
$clicks = [];
$labels = [];

for ($i=13; $i>=0; $i--) {
  $day = date('Y-m-d', strtotime("-$i days"));
  $labels[] = $day;

  $stmt = $pdo->prepare("SELECT COUNT(*) c FROM clicks WHERE ref_code=? AND DATE(created_at)=?");
  $stmt->execute([$ref, $day]);
  $clicks[] = (int)$stmt->fetchColumn();
}

// enkle summer (demo logikk)
$leads = 0;
$customers = 0;
$bonus_ore = 0;

echo json_encode([
  'ok'=>true,
  'labels'=>$labels,
  'clicks_series'=>$clicks,
  'leads_series'=>array_fill(0,14,0),
  'customers_series'=>array_fill(0,14,0),
  'clicks'=>array_sum($clicks),
  'leads'=>$leads,
  'customers'=>$customers,
  'bonus_ore'=>$bonus_ore
]);
