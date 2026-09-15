<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

function geocodeRespond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $action = trim((string) ($_GET['action'] ?? 'search'));
    if ($action === 'reverse') {
        $latitude = filter_var($_GET['lat'] ?? null, FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($_GET['lon'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($latitude === false || $longitude === false) {
            geocodeRespond(422, ['success' => false, 'message' => 'Koordinat peta tidak valid.']);
        }
        $result = reverseGeocode((float) $latitude, (float) $longitude);
        geocodeRespond(200, ['success' => true, 'location' => $result]);
    }

    $query = trim((string) ($_GET['q'] ?? ''));
    if (strlen($query) < 3) {
        geocodeRespond(422, ['success' => false, 'message' => 'Ketik minimal 3 karakter alamat.']);
    }
    $cacheKey = 'nominatim_search_' . md5(strtolower($query));
    $results = $_SESSION[$cacheKey] ?? null;
    if (!is_array($results)) {
        $results = nominatimRequest('search', ['format' => 'jsonv2', 'addressdetails' => 1, 'limit' => 5, 'countrycodes' => 'id', 'q' => $query]);
        $_SESSION[$cacheKey] = $results;
    }
    geocodeRespond(200, ['success' => true, 'locations' => $results]);
} catch (Throwable $exception) {
    geocodeRespond(502, ['success' => false, 'message' => $exception->getMessage() ?: 'OpenStreetMap tidak dapat dihubungi.']);
}