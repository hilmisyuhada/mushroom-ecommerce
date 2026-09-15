<?php
declare(strict_types=1);

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'mushroom_organik';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPassword = getenv('DB_PASSWORD') ?: '';
$dbPort = getenv('DB_PORT') ?: '3306';
$localConfig = is_file(__DIR__ . '/config.local.php') ? (require __DIR__ . '/config.local.php') : [];
$rajaongkirApiKey = getenv('RAJAONGKIR_API_KEY') ?: (string) ($localConfig['RAJAONGKIR_API_KEY'] ?? '');
$rajaongkirOriginCityId = getenv('RAJAONGKIR_ORIGIN_CITY_ID') ?: (string) ($localConfig['RAJAONGKIR_ORIGIN_CITY_ID'] ?? '');
$rajaongkirOriginCity = getenv('RAJAONGKIR_ORIGIN_CITY') ?: (string) ($localConfig['RAJAONGKIR_ORIGIN_CITY'] ?? 'Medan');
$rajaongkirCouriers = getenv('RAJAONGKIR_COURIERS') ?: (string) ($localConfig['RAJAONGKIR_COURIERS'] ?? 'jne:pos:tiki');
$shippingOriginQuery = getenv('SHIPPING_ORIGIN_QUERY') ?: (string) ($localConfig['SHIPPING_ORIGIN_QUERY'] ?? $rajaongkirOriginCity);
$nominatimUserAgent = getenv('NOMINATIM_USER_AGENT') ?: (string) ($localConfig['NOMINATIM_USER_AGENT'] ?? 'MushroomOrganik/1.0 (shipping contact required)');

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    http_response_code(500);
    if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Database belum terhubung. Periksa konfigurasi MySQL dan jalankan database.sql.']);
    } else {
        exit('Database belum terhubung. Periksa konfigurasi DB_HOST, DB_NAME, DB_USER, dan DB_PASSWORD.');
    }
    exit;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireCsrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
        http_response_code(419);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Sesi keamanan tidak valid. Muat ulang halaman dan coba lagi.']);
        exit;
    }
}

function ensureOrderStatusColumn(): void
{
    global $pdo;
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;
    $statement = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'order_status'");
    $statement->execute();
    if ((int) $statement->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN order_status VARCHAR(60) NOT NULL DEFAULT 'Menunggu pembayaran' AFTER payment_status");
    }
}

function ensureStoreSettingsTable(): void
{
    global $pdo;
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;
    $pdo->exec("CREATE TABLE IF NOT EXISTS store_settings (setting_key VARCHAR(80) PRIMARY KEY, setting_value TEXT NULL, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB");
}

function storeSettings(): array
{
    global $pdo;
    ensureStoreSettingsTable();
    $settings = [];
    foreach ($pdo->query('SELECT setting_key, setting_value FROM store_settings')->fetchAll() as $setting) {
        $settings[$setting['setting_key']] = (string) $setting['setting_value'];
    }
    return $settings;
}

function rajaOngkirRequest(string $path, array $query = [], string $method = 'GET'): array
{
    global $rajaongkirApiKey;
    if ($rajaongkirApiKey === '') {
        throw new RuntimeException('RAJAONGKIR_API_KEY belum dikonfigurasi.');
    }
    $url = 'https://rajaongkir.komerce.id/api/v1/' . ltrim($path, '/');
    if ($method === 'GET' && $query !== []) {
        $url .= '?' . http_build_query($query);
    }
    $headers = ['Accept: application/json', 'key: ' . $rajaongkirApiKey];
    if (function_exists('curl_init')) {
        $handle = curl_init($url);
        curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => 15, CURLOPT_CUSTOMREQUEST => $method]);
        if ($method !== 'GET') {
            curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($query));
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        }
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);
    } else {
        $context = stream_context_create(['http' => ['method' => $method, 'header' => implode("\r\n", $headers), 'content' => $method === 'GET' ? '' : http_build_query($query), 'timeout' => 15, 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $context);
        $status = (int) (preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0] ?? '', $matches) ? $matches[1] : 0);
        $error = $body === false ? 'Request RajaOngkir gagal.' : '';
    }
    $result = is_string($body) ? json_decode($body, true) : null;
    if ($error !== '' || $status < 200 || $status >= 300 || !is_array($result)) {
        $apiMessage = is_array($result) ? (string) ($result['meta']['message'] ?? $result['message'] ?? '') : '';
        $detail = $apiMessage !== '' ? ' ' . $apiMessage : ($error !== '' ? ' ' . $error : '');
        throw new RuntimeException('RajaOngkir gagal (HTTP ' . $status . ').' . $detail);
    }
    return $result;
}

function nominatimRequest(string $path, array $query): array
{
    global $nominatimUserAgent;
    $url = 'https://nominatim.openstreetmap.org/' . ltrim($path, '/') . '?' . http_build_query($query);
    $headers = ['Accept: application/json', 'User-Agent: ' . $nominatimUserAgent];
    if (function_exists('curl_init')) {
        $handle = curl_init($url);
        curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => 12]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);
    } else {
        $context = stream_context_create(['http' => ['header' => implode("\r\n", $headers), 'timeout' => 12, 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $context);
        $status = (int) (preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0] ?? '', $matches) ? $matches[1] : 0);
        $error = $body === false ? 'Nominatim tidak dapat dihubungi.' : '';
    }
    $result = is_string($body) ? json_decode($body, true) : null;
    if ($error !== '' || $status < 200 || $status >= 300 || !is_array($result)) {
        throw new RuntimeException('OpenStreetMap tidak dapat dihubungi.');
    }
    return $result;
}

function reverseGeocode(float $destLat, float $destLon): array
{
    if ($destLat < -11 || $destLat > 6 || $destLon < 95 || $destLon > 142) {
        throw new RuntimeException('Koordinat tujuan berada di luar wilayah Indonesia.');
    }
    $cacheKey = 'nominatim_reverse_' . md5(round($destLat, 5) . ',' . round($destLon, 5));
    if (is_array($_SESSION[$cacheKey] ?? null)) {
        return $_SESSION[$cacheKey];
    }
    $result = nominatimRequest('reverse', ['format' => 'jsonv2', 'lat' => $destLat, 'lon' => $destLon, 'zoom' => 18, 'addressdetails' => 1]);
    if (empty($result['address']) || !is_array($result['address'])) {
        throw new RuntimeException('Kecamatan tujuan tidak ditemukan dari OpenStreetMap.');
    }
    $_SESSION[$cacheKey] = $result;
    return $result;
}

function shippingName(string $value): string
{
    $value = function_exists('iconv') ? (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : $value;
    return strtolower(preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '');
}

function regularRajaOngkirCouriers(): string
{
    global $rajaongkirCouriers;
    $allowed = ['jne', 'sicepat', 'ide', 'sap', 'jnt', 'ninja', 'tiki', 'lion', 'anteraja', 'pos', 'ncs', 'rex', 'rpx', 'sentral', 'star', 'wahana'];
    $configured = preg_split('/[:;,|\s]+/', strtolower($rajaongkirCouriers), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $couriers = array_values(array_intersect($allowed, $configured));
    if ($couriers === []) {
        $couriers = ['jne', 'jnt', 'pos'];
    }
    return implode(':', $couriers);
}

function resolveRajaOngkirRegionFromAddress(array $reverse): array
{
    $address = $reverse['address'] ?? [];
    $district = (string) ($address['municipality'] ?? $address['district'] ?? $address['county'] ?? $address['suburb'] ?? '');
    $city = (string) ($address['city'] ?? $address['town'] ?? $address['municipality'] ?? $address['county'] ?? '');
    $province = (string) ($address['state'] ?? $address['province'] ?? '');
    $searchTerms = array_values(array_unique(array_filter([$district, $city, $province])));
    if ($searchTerms === []) {
        throw new RuntimeException('Kota atau kecamatan tidak tersedia dari OpenStreetMap.');
    }
    $cacheKey = 'raja_destination_v3_' . md5(implode('|', array_map('shippingName', $searchTerms)));
    if (is_array($_SESSION[$cacheKey] ?? null)) {
        return $_SESSION[$cacheKey];
    }
    $destination = rajaOngkirRequest('destination/domestic-destination', ['search' => implode(', ', $searchTerms), 'limit' => 50, 'page' => 1]);
    $destinations = $destination['data'] ?? [];
    if (!is_array($destinations) || $destinations === []) {
        throw new RuntimeException('Alamat dari OpenStreetMap tidak ditemukan di RajaOngkir.');
    }
    $expected = array_map('shippingName', $searchTerms);
    $selected = null;
    foreach ($destinations as $candidate) {
        $label = shippingName((string) ($candidate['label'] ?? $candidate['name'] ?? ''));
        $matches = 0;
        foreach ($expected as $term) {
            if ($term !== '' && str_contains($label, $term)) {
                $matches++;
            }
        }
        if ($matches >= 2 || ($matches === 1 && count($expected) === 1)) {
            $selected = $candidate;
            break;
        }
    }
    $selected ??= $destinations[0] ?? null;
    if (!is_array($selected) || empty($selected['id'])) {
        throw new RuntimeException('ID tujuan RajaOngkir tidak ditemukan.');
    }
    return $_SESSION[$cacheKey] = ['id' => (string) $selected['id'], 'label' => (string) ($selected['label'] ?? $selected['name'] ?? implode(', ', $searchTerms))];
}

function shippingOriginCoordinates(): array
{
    global $shippingOriginQuery;
    $cacheKey = 'nominatim_origin_' . md5($shippingOriginQuery);
    if (is_array($_SESSION[$cacheKey] ?? null)) {
        return $_SESSION[$cacheKey];
    }
    $results = nominatimRequest('search', ['format' => 'jsonv2', 'q' => $shippingOriginQuery, 'countrycodes' => 'id', 'limit' => 1]);
    if (!isset($results[0]['lat'], $results[0]['lon'])) {
        throw new RuntimeException('Lokasi asal toko tidak ditemukan di OpenStreetMap.');
    }
    return $_SESSION[$cacheKey] = ['lat' => (float) $results[0]['lat'], 'lon' => (float) $results[0]['lon']];
}

function haversineKm(float $originLat, float $originLon, float $destLat, float $destLon): float
{
    $earthRadius = 6371.0088;
    $latDelta = deg2rad($destLat - $originLat);
    $lonDelta = deg2rad($destLon - $originLon);
    $a = sin($latDelta / 2) ** 2 + cos(deg2rad($originLat)) * cos(deg2rad($destLat)) * sin($lonDelta / 2) ** 2;
    return $earthRadius * 2 * asin(min(1, sqrt($a)));
}

function calculateInstantShipping(float $destLat, float $destLon): array
{
    $origin = shippingOriginCoordinates();
    $distance = haversineKm($origin['lat'], $origin['lon'], $destLat, $destLon);
    if ($distance > 40) {
        throw new RuntimeException('Kurir instant hanya tersedia sampai jarak 40 km.');
    }
    $cost = 14000 + max(0, (int) ceil($distance) - 4) * 2500;
    return [['name' => 'GoSend / SPX Instant', 'cost' => $cost, 'days' => 'Hari ini', 'code' => 'instant', 'distanceKm' => round($distance, 2), 'mode' => 'instant']];
}

function calculateRegularShipping(float $destLat, float $destLon, int $weight): array
{
    global $rajaongkirOriginCityId;
    $couriers = regularRajaOngkirCouriers();
    $reverse = reverseGeocode($destLat, $destLon);
    $region = resolveRajaOngkirRegionFromAddress($reverse);
    $cacheKey = 'raja_rates_v4_' . md5($region['id'] . '|' . $weight . '|' . $couriers);
    if (is_array($_SESSION[$cacheKey] ?? null) && ($_SESSION[$cacheKey]['expires'] ?? 0) > time()) {
        return $_SESSION[$cacheKey]['options'];
    }
    if ($rajaongkirOriginCityId === '') {
        throw new RuntimeException('RAJAONGKIR_ORIGIN_CITY_ID wajib diisi untuk ongkir reguler.');
    }
    $result = rajaOngkirRequest('calculate/domestic-cost', ['origin' => $rajaongkirOriginCityId, 'destination' => $region['id'], 'weight' => $weight, 'courier' => $couriers], 'POST');
    $options = [];
    foreach (($result['data'] ?? []) as $row) {
        $cost = filter_var($row['cost'] ?? $row['price'] ?? null, FILTER_VALIDATE_INT);
        if ($cost !== false && $cost >= 0) {
            $courier = trim((string) ($row['courier'] ?? $row['code'] ?? $couriers));
            $options[] = ['name' => trim((string) ($row['service'] ?? $row['name'] ?? 'Reguler')) . ' (' . strtoupper($courier) . ')', 'cost' => $cost, 'days' => trim((string) ($row['etd'] ?? $row['duration'] ?? '-')), 'code' => $courier, 'mode' => 'reguler', 'destinationId' => $region['id']];
        }
    }
    if ($options === []) {
        throw new RuntimeException('Layanan ongkir reguler tidak tersedia untuk kecamatan ini.');
    }
    $_SESSION[$cacheKey] = ['expires' => time() + 1800, 'options' => $options];
    return $options;
}

function calculateShippingQuote(string $mode, float $destLat, float $destLon, int $weight): array
{
    if ($mode === 'instant') {
        return calculateInstantShipping($destLat, $destLon);
    }
    if ($mode === 'reguler') {
        return calculateRegularShipping($destLat, $destLon, $weight);
    }
    throw new RuntimeException('Mode pengiriman tidak valid.');
}

