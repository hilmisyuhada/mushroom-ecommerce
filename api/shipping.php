<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

function shippingRespond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    shippingRespond(405, ['success' => false, 'message' => 'Metode tidak diizinkan.']);
}

requireCsrf();
$input = json_decode(file_get_contents('php://input'), true);
$mode = trim((string) ($input['mode'] ?? ''));
$latitude = filter_var($input['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
$longitude = filter_var($input['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
$weight = filter_var($input['weight'] ?? null, FILTER_VALIDATE_INT);
if (!in_array($mode, ['instant', 'reguler'], true) || $latitude === false || $longitude === false || $latitude < -11 || $latitude > 6 || $longitude < 95 || $longitude > 142 || $weight === false || $weight < 1 || $weight > 30000) {
    shippingRespond(422, ['success' => false, 'message' => 'Pilih alamat dari OpenStreetMap dan pastikan berat pengiriman valid.']);
}

try {
    shippingRespond(200, ['success' => true, 'options' => calculateShippingQuote($mode, (float) $latitude, (float) $longitude, (int) $weight)]);
} catch (Throwable $exception) {
    shippingRespond(502, ['success' => false, 'message' => $exception->getMessage() ?: 'Ongkir gagal dihitung.']);
}
