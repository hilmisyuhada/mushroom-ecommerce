<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

function adminRespond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$user = currentUser();
if (!$user || $user['role'] !== 'admin') {
    adminRespond(403, ['success' => false, 'message' => 'Akses admin diperlukan.']);
}
try {
    ensureOrderStatusColumn();
} catch (Throwable $exception) {
    adminRespond(500, ['success' => false, 'message' => 'Kolom status pesanan belum tersedia. Jalankan database_migration_v4.sql.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $dateFrom = trim((string) ($_GET['date_from'] ?? ''));
    $dateTo = trim((string) ($_GET['date_to'] ?? ''));
    if (($dateFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) || ($dateTo !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo))) {
        adminRespond(422, ['success' => false, 'message' => 'Format tanggal filter tidak valid.']);
    }
    try {
        $conditions = [];
        $parameters = [];
        if ($dateFrom !== '') {
            $conditions[] = 'o.created_at >= ?';
            $parameters[] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo !== '') {
            $conditions[] = 'o.created_at < DATE_ADD(?, INTERVAL 1 DAY)';
            $parameters[] = $dateTo . ' 00:00:00';
        }
        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $statement = $pdo->prepare(
            "SELECT o.id, o.customer_name, o.customer_phone, o.city, o.province, o.total, o.payment_status, o.proof_file, o.order_status, o.admin_note, o.created_at, u.email
             FROM orders o LEFT JOIN users u ON u.id = o.user_id{$where} ORDER BY o.created_at DESC"
        );
        $statement->execute($parameters);
    } catch (Throwable $exception) {
        adminRespond(500, ['success' => false, 'message' => 'Data pesanan gagal dimuat.']);
    }
    adminRespond(200, ['success' => true, 'orders' => $statement->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adminRespond(405, ['success' => false, 'message' => 'Metode tidak diizinkan.']);
}

requireCsrf();
$input = json_decode(file_get_contents('php://input'), true);
$status = trim((string) ($input['status'] ?? ''));
$paymentStatus = trim((string) ($input['paymentStatus'] ?? ''));
$orderId = trim((string) ($input['orderId'] ?? ''));
$note = trim((string) ($input['note'] ?? ''));
$allowedStatuses = ['Menunggu pembayaran', 'Sedang dikemas', 'Telah dikirim', 'Sudah sampai', 'Dibatalkan'];
$allowedPaymentStatuses = ['Menunggu verifikasi pembayaran', 'Pembayaran disetujui', 'Pembayaran ditolak'];

if (!in_array($status, $allowedStatuses, true) || !in_array($paymentStatus, $allowedPaymentStatuses, true) || !preg_match('/^OIS-[0-9]{8}-[A-F0-9]{4}$/', $orderId) || strlen($note) > 500) {
    adminRespond(422, ['success' => false, 'message' => 'Data status tidak valid.']);
}

$exists = $pdo->prepare('SELECT 1 FROM orders WHERE id = ? LIMIT 1');
$exists->execute([$orderId]);
if (!$exists->fetchColumn()) {
    adminRespond(404, ['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
}

$statement = $pdo->prepare("UPDATE orders SET payment_status = ?, payment_verified_at = CASE WHEN ? = 'Pembayaran disetujui' THEN NOW() ELSE payment_verified_at END, order_status = CASE WHEN ? = 'Pembayaran disetujui' AND order_status = 'Menunggu pembayaran' THEN 'Sedang dikemas' ELSE ? END, admin_note = ? WHERE id = ?");
$statement->execute([$paymentStatus, $paymentStatus, $paymentStatus, $status, $note !== '' ? $note : null, $orderId]);

adminRespond(200, ['success' => true]);
