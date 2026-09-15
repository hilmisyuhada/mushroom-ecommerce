<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

function settingsRespond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$user = currentUser();
if (!$user || $user['role'] !== 'admin') {
    settingsRespond(403, ['success' => false, 'message' => 'Akses admin diperlukan.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    settingsRespond(200, ['success' => true, 'settings' => storeSettings()]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    settingsRespond(405, ['success' => false, 'message' => 'Metode tidak diizinkan.']);
}
requireCsrf();
$input = json_decode(file_get_contents('php://input'), true);
$allowed = ['about_title', 'about_description', 'about_badge_title', 'about_badge_text', 'contact_whatsapp', 'contact_email', 'contact_instagram'];
if (!is_array($input)) {
    settingsRespond(400, ['success' => false, 'message' => 'Data profil tidak valid.']);
}
try {
    ensureStoreSettingsTable();
    $statement = $pdo->prepare('INSERT INTO store_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    foreach ($allowed as $key) {
        $value = trim((string) ($input[$key] ?? ''));
        if (strlen($value) > 3000) {
            settingsRespond(422, ['success' => false, 'message' => 'Isi profil terlalu panjang.']);
        }
        $statement->execute([$key, $value]);
    }
    settingsRespond(200, ['success' => true]);
} catch (Throwable $exception) {
    settingsRespond(500, ['success' => false, 'message' => 'Profil toko gagal disimpan.']);
}
