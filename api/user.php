<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
$user = currentUser();
if (!$user || $user['role'] !== 'customer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Silakan login sebagai user.']);
    exit;
}
try {
    ensureOrderStatusColumn();
    $statement = $pdo->prepare('SELECT id, customer_name, customer_phone, city, province, address, shipping_name, shipping_cost, subtotal, total, payment_status, order_status, admin_note, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC');
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Riwayat pesanan belum siap. Jalankan database_migration_v4.sql.']);
    exit;
}
$statement->execute([$user['id']]);
$orders = $statement->fetchAll();
foreach ($orders as &$order) {
    $items = $pdo->prepare('SELECT product_name, unit_price, quantity, line_total FROM order_items WHERE order_id = ?');
    $items->execute([$order['id']]);
    $order['items'] = $items->fetchAll();
}
unset($order);
echo json_encode(['success' => true, 'user' => $user, 'orders' => $orders], JSON_UNESCAPED_UNICODE);
