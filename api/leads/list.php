<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';

$user = requireLogin();
$role = $user['role'];

if (!in_array($role, ['super_admin', 'store_admin', 'ambassador'], true)) {
    jsonResponse(403, ['ok' => false, 'error' => 'forbidden_role']);
}

$where = [];
$params = [];

if ($role === 'super_admin') {
    if (!empty($_GET['store_id']) && (int)$_GET['store_id'] > 0) {
        $where[] = 'l.store_id = :store_id';
        $params['store_id'] = (int)$_GET['store_id'];
    }
} elseif ($role === 'store_admin') {
    $where[] = 'l.store_id = :store_id';
    $params['store_id'] = (int)($user['store_id'] ?? 0);
} else {
    $where[] = 'l.ambassador_id = :ambassador_id';
    $params['ambassador_id'] = (int)($user['ambassador_id'] ?? 0);
}

if (!empty($_GET['status'])) {
    $allowed = ['open', 'meeting_booked', 'offer_sent', 'approved', 'rejected'];
    $s = $_GET['status'];
    if (in_array($s, $allowed, true)) {
        $where[] = 'l.status = :status';
        $params['status'] = $s;
    }
}

if ($role !== 'ambassador' && !empty($_GET['ambassador_id']) && (int)$_GET['ambassador_id'] > 0) {
    $where[] = 'l.ambassador_id = :filter_amb_id';
    $params['filter_amb_id'] = (int)$_GET['ambassador_id'];
}

if (!empty($_GET['date_from'])) {
    $where[] = 'l.created_at >= :date_from';
    $params['date_from'] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where[] = 'l.created_at <= :date_to';
    $params['date_to'] = $_GET['date_to'] . ' 23:59:59';
}
if (!empty($_GET['search'])) {
    $where[] = '(l.company_name LIKE :s1 OR l.contact_person LIKE :s2 OR l.contact_email LIKE :s3)';
    $sv = '%' . $_GET['search'] . '%';
    $params['s1'] = $sv;
    $params['s2'] = $sv;
    $params['s3'] = $sv;
}

$whereStr = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT l.*,
    COALESCE(a.name, a.ambassador_name) AS ambassador_name,
    s.name AS store_name
FROM leads l
LEFT JOIN ambassadors a ON l.ambassador_id = a.id
LEFT JOIN stores s ON l.store_id = s.id
$whereStr
ORDER BY l.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pipelineSql = "SELECT status, COUNT(*) AS cnt, COALESCE(SUM(offer_amount),0) AS total
FROM leads l $whereStr GROUP BY status";

$pipelineStmt = $pdo->prepare($pipelineSql);
$pipelineStmt->execute($params);
$pipelineRows = $pipelineStmt->fetchAll(PDO::FETCH_ASSOC);

$pipeline = [
    'open'           => ['count' => 0, 'total' => 0.0],
    'meeting_booked' => ['count' => 0, 'total' => 0.0],
    'offer_sent'     => ['count' => 0, 'total' => 0.0],
    'approved'       => ['count' => 0, 'total' => 0.0],
    'rejected'       => ['count' => 0, 'total' => 0.0],
];
foreach ($pipelineRows as $row) {
    $key = $row['status'];
    if (isset($pipeline[$key])) {
        $pipeline[$key] = ['count' => (int)$row['cnt'], 'total' => (float)$row['total']];
    }
}

jsonResponse(200, ['ok' => true, 'leads' => $leads, 'pipeline' => $pipeline, 'role' => $role]);
