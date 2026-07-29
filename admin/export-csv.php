<?php
/**
 * admin/export-csv.php — download bookings (optionally filtered) as CSV
 */
require_once __DIR__ . '/auth.php';
admin_require_login();
require_once __DIR__ . '/../db.php';

$statusFilter = $_GET['status'] ?? '';
$allowedStatuses = ['new', 'contacted', 'confirmed', 'cancelled'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}
$search = trim($_GET['q'] ?? '');

$where = [];
$params = [];
if ($statusFilter !== '') {
    $where[] = 'status = :status';
    $params[':status'] = $statusFilter;
}
if ($search !== '') {
    $where[] = '(full_name LIKE :q OR email LIKE :q OR phone LIKE :q OR program LIKE :q)';
    $params[':q'] = '%' . $search . '%';
}
$sql = 'SELECT id, full_name, email, phone, program, preferred_dates, message, status, created_at FROM bookings';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY created_at DESC';

$pdo = get_db_connection();
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sarana-bookings-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['ID', 'Name', 'Email', 'Phone', 'Program', 'Preferred Dates', 'Message', 'Status', 'Received At']);
while ($row = $stmt->fetch()) {
    fputcsv($out, [
        $row['id'], $row['full_name'], $row['email'], $row['phone'],
        $row['program'], $row['preferred_dates'], $row['message'],
        $row['status'], $row['created_at'],
    ]);
}
fclose($out);
exit;
