<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

function productRespond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$user = currentUser();
if (!$user || $user['role'] !== 'admin') {
    productRespond(403, ['success' => false, 'message' => 'Akses admin diperlukan.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    productRespond(200, ['success' => true, 'products' => $pdo->query('SELECT id, name, price, image, weight_gram, category, badge, claim, description, composition, highlight_one_icon, highlight_one_title, highlight_one_text, highlight_two_icon, highlight_two_title, highlight_two_text, is_active FROM products ORDER BY created_at DESC')->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    productRespond(405, ['success' => false, 'message' => 'Metode tidak diizinkan.']);
}
requireCsrf();
$input = $_SERVER['CONTENT_TYPE'] ?? '';
$input = str_contains($input, 'multipart/form-data') ? $_POST : json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    productRespond(400, ['success' => false, 'message' => 'Data produk tidak valid.']);
}

$action = $input['action'] ?? '';
$id = trim((string) ($input['id'] ?? ''));
$name = trim((string) ($input['name'] ?? ''));
$image = trim((string) ($input['image'] ?? ''));
$category = trim((string) ($input['category'] ?? ''));
$badge = trim((string) ($input['badge'] ?? ''));
$claim = trim((string) ($input['claim'] ?? ''));
$description = trim((string) ($input['description'] ?? ''));
$composition = trim((string) ($input['composition'] ?? ''));
$highlightOneIcon = 'fa-seedling';
$highlightOneTitle = trim((string) ($input['highlightOneTitle'] ?? ''));
$highlightOneText = trim((string) ($input['highlightOneText'] ?? ''));
$highlightTwoIcon = 'fa-bowl-food';
$highlightTwoTitle = trim((string) ($input['highlightTwoTitle'] ?? ''));
$highlightTwoText = trim((string) ($input['highlightTwoText'] ?? ''));
$price = filter_var($input['price'] ?? null, FILTER_VALIDATE_INT);
$weight = filter_var($input['weight'] ?? null, FILTER_VALIDATE_INT);
$isActive = !empty($input['isActive']) ? 1 : 0;

if ($action === 'delete') {
    if (!preg_match('/^[a-z0-9-]{1,80}$/', $id)) {
        productRespond(422, ['success' => false, 'message' => 'ID produk tidak valid.']);
    }
    $statement = $pdo->prepare('UPDATE products SET is_active = 0 WHERE id = ?');
    $statement->execute([$id]);
    productRespond(200, ['success' => true]);
}

if (!preg_match('/^[a-z0-9-]{1,80}$/', $id)) {
    $id = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', $name), '-'));
}
if (!preg_match('/^[a-z0-9-]{1,80}$/', $id)) {
    productRespond(422, ['success' => false, 'message' => 'ID produk tidak valid.']);
}

if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['image'];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
        productRespond(422, ['success' => false, 'message' => 'Gambar maksimal 5 MB.']);
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        productRespond(422, ['success' => false, 'message' => 'Gambar harus JPG, PNG, atau WEBP.']);
    }
    $directory = dirname(__DIR__) . '/assets/images/products';
    $filename = $id . '-' . bin2hex(random_bytes(4)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
        productRespond(500, ['success' => false, 'message' => 'Gambar gagal disimpan.']);
    }
    $image = 'assets/images/products/' . $filename;
}
if ($image === '' && $action === 'update') {
    $existing = $pdo->prepare('SELECT image FROM products WHERE id = ? LIMIT 1');
    $existing->execute([$id]);
    $image = (string) ($existing->fetchColumn() ?: '');
}

if ($name === '' || strlen($name) > 160 || $price === false || $price < 0 || $price > 100000000 || $weight === false || $weight < 1 || $weight > 10000 || $image === '' || $category === '' || strlen($category) > 120 || strlen($badge) > 80 || strlen($claim) > 80 || strlen($description) > 5000 || strlen($composition) > 500 || strlen($highlightOneIcon) > 60 || strlen($highlightOneTitle) > 120 || strlen($highlightOneText) > 180 || strlen($highlightTwoIcon) > 60 || strlen($highlightTwoTitle) > 120 || strlen($highlightTwoText) > 180) {
    productRespond(422, ['success' => false, 'message' => 'Data produk atau detail produk tidak valid.']);
}

try {
    if ($action === 'update') {
        $statement = $pdo->prepare('UPDATE products SET name = ?, price = ?, image = ?, weight_gram = ?, category = ?, badge = ?, claim = ?, description = ?, composition = ?, highlight_one_icon = ?, highlight_one_title = ?, highlight_one_text = ?, highlight_two_icon = ?, highlight_two_title = ?, highlight_two_text = ?, is_active = ? WHERE id = ?');
        $statement->execute([$name, $price, $image, $weight, $category, $badge ?: null, $claim ?: null, $description ?: null, $composition ?: null, $highlightOneIcon ?: null, $highlightOneTitle ?: null, $highlightOneText ?: null, $highlightTwoIcon ?: null, $highlightTwoTitle ?: null, $highlightTwoText ?: null, $isActive, $id]);
    } elseif ($action === 'create') {
        $statement = $pdo->prepare('INSERT INTO products (id, name, price, image, weight_gram, category, badge, claim, description, composition, highlight_one_icon, highlight_one_title, highlight_one_text, highlight_two_icon, highlight_two_title, highlight_two_text, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([$id, $name, $price, $image, $weight, $category, $badge ?: null, $claim ?: null, $description ?: null, $composition ?: null, $highlightOneIcon ?: null, $highlightOneTitle ?: null, $highlightOneText ?: null, $highlightTwoIcon ?: null, $highlightTwoTitle ?: null, $highlightTwoText ?: null, $isActive]);
    } else {
        productRespond(422, ['success' => false, 'message' => 'Aksi produk tidak dikenal.']);
    }
} catch (PDOException $exception) {
    productRespond($exception->getCode() === '23000' ? 409 : 500, ['success' => false, 'message' => $exception->getCode() === '23000' ? 'ID produk sudah digunakan.' : 'Produk gagal disimpan.']);
}
productRespond(200, ['success' => true]);
