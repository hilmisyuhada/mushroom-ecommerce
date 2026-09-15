<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Metode tidak diizinkan.']);
}

requireCsrf();
$user = currentUser();
if ($user === null) {
    respond(401, ['success' => false, 'message' => 'Silakan login sebelum checkout.']);
}

$input = isset($_POST['payload']) ? json_decode((string) $_POST['payload'], true) : json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    respond(400, ['success' => false, 'message' => 'Data pesanan tidak valid.']);
}

$customer = $input['customer'] ?? [];
$items = $input['items'] ?? [];
$shipping = $input['shipping'] ?? [];
$shippingMode = trim((string) ($input['shippingMode'] ?? ''));
$latitude = filter_var($input['destinationLat'] ?? null, FILTER_VALIDATE_FLOAT);
$longitude = filter_var($input['destinationLon'] ?? null, FILTER_VALIDATE_FLOAT);
$requiredCustomer = ['name', 'phone', 'province', 'city', 'address', 'postalCode'];

foreach ($requiredCustomer as $field) {
    if (!is_string($customer[$field] ?? null) || trim($customer[$field]) === '') {
        respond(422, ['success' => false, 'message' => 'Data alamat belum lengkap.']);
    }
}

if (!is_array($items) || count($items) === 0 || !in_array($shippingMode, ['instant', 'reguler'], true) || $latitude === false || $longitude === false || !is_string($shipping['name'] ?? null) || !is_numeric($shipping['cost'] ?? null)) {
    respond(422, ['success' => false, 'message' => 'Produk atau pengiriman tidak valid.']);
}

$productIds = [];
$quantities = [];
foreach ($items as $item) {
    $id = $item['id'] ?? '';
    $quantity = filter_var($item['qty'] ?? null, FILTER_VALIDATE_INT);
    if (!is_string($id) || !preg_match('/^[a-z0-9-]+$/', $id) || $quantity === false || $quantity < 1 || $quantity > 99) {
        respond(422, ['success' => false, 'message' => 'Isi keranjang tidak valid.']);
    }
    $productIds[] = $id;
    $quantities[$id] = ($quantities[$id] ?? 0) + $quantity;
}

$placeholders = implode(',', array_fill(0, count($productIds), '?'));
$stmt = $pdo->prepare("SELECT id, name, price, weight_gram FROM products WHERE is_active = 1 AND id IN ({$placeholders})");
$stmt->execute($productIds);
$products = $stmt->fetchAll();
$productMap = [];
foreach ($products as $product) {
    $productMap[$product['id']] = $product;
}

if (count($productMap) !== count($quantities)) {
    respond(422, ['success' => false, 'message' => 'Ada produk yang sudah tidak tersedia.']);
}

$subtotal = 0;
$totalWeight = 0;
foreach ($quantities as $productId => $quantity) {
    $subtotal += (int) $productMap[$productId]['price'] * $quantity;
    $totalWeight += (int) $productMap[$productId]['weight_gram'] * $quantity;
}

$shippingCost = filter_var($shipping['cost'], FILTER_VALIDATE_INT);
try {
    $validShipping = calculateShippingQuote($shippingMode, (float) $latitude, (float) $longitude, max(1, $totalWeight));
} catch (Throwable $exception) {
    respond(502, ['success' => false, 'message' => $exception->getMessage() ?: 'Ongkir gagal diverifikasi.']);
}
$validRate = null;
foreach ($validShipping as $option) {
    if ($option['name'] === trim($shipping['name']) && $option['cost'] === $shippingCost) {
        $validRate = $option;
        break;
    }
}
if ($shippingCost === false || $validRate === null) {
    respond(422, ['success' => false, 'message' => 'Pilihan ongkir sudah berubah. Hitung ulang ongkir sebelum checkout.']);
}

$orderId = 'OIS-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
$total = $subtotal + $shippingCost;

$proofFile = $_FILES['proof'] ?? null;
if (!is_array($proofFile) || $proofFile['error'] !== UPLOAD_ERR_OK || $proofFile['size'] > 5 * 1024 * 1024) {
    respond(422, ['success' => false, 'message' => 'Bukti pembayaran wajib diunggah dan maksimal 5 MB.']);
}
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($proofFile['tmp_name']);
$extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
if (!isset($extensions[$mime])) {
    respond(422, ['success' => false, 'message' => 'Bukti pembayaran harus JPG, PNG, atau WEBP.']);
}
$proofRelativePath = 'assets/documents/payments/' . $orderId . '-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];

try {
    $pdo->beginTransaction();
    $order = $pdo->prepare(
        'INSERT INTO orders (id, user_id, customer_name, customer_phone, province, city, address, postal_code, note, shipping_name, shipping_cost, subtotal, total, proof_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $order->execute([
        $orderId,
        $user['id'],
        trim($customer['name']),
        trim($customer['phone']),
        trim($customer['province']),
        trim($customer['city']),
        trim($customer['address']),
        trim($customer['postalCode']),
        trim((string) ($customer['note'] ?? '')) ?: null,
        trim($shipping['name']),
        $shippingCost,
        $subtotal,
        $total,
        $proofRelativePath,
    ]);

    $orderItem = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($quantities as $productId => $quantity) {
        $product = $productMap[$productId];
        $orderItem->execute([$orderId, $productId, $product['name'], $product['price'], $quantity, (int) $product['price'] * $quantity]);
    }

    if (!move_uploaded_file($proofFile['tmp_name'], dirname(__DIR__) . '/' . $proofRelativePath)) {
        throw new RuntimeException('Bukti pembayaran gagal disimpan.');
    }
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respond(500, ['success' => false, 'message' => $exception->getMessage() ?: 'Pesanan gagal disimpan.']);
}

respond(201, ['success' => true, 'order' => ['id' => $orderId, 'subtotal' => $subtotal, 'shipping' => $shippingCost, 'total' => $total, 'paymentStatus' => 'Menunggu verifikasi pembayaran']]);
