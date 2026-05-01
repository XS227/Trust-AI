<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';
requireRole('super_admin');
$b = readJsonBody();
$id = (int)($b['id'] ?? 0);
if ($id <= 0) jsonResponse(400, ['ok' => false, 'error' => 'id_required']);

$s = $pdo->prepare('SELECT status FROM stores WHERE id=:id');
$s->execute(['id' => $id]);
$cur = strtolower(trim((string)$s->fetchColumn()));
if ($cur === '') jsonResponse(404, ['ok' => false, 'error' => 'store_not_found']);
$next = in_array($cur, ['active', 'aktiv'], true) ? 'inactive' : 'active';
$pdo->prepare('UPDATE stores SET status=:s WHERE id=:id')->execute(['s' => $next, 'id' => $id]);
jsonResponse(200, ['ok' => true, 'data' => ['id' => $id, 'status' => $next]]);
