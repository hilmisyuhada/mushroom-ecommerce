<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

function authRespond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'me' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    authRespond(200, ['success' => true, 'user' => currentUser(), 'csrfToken' => csrfToken()]);
}

if ($action === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    authRespond(200, ['success' => true]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    authRespond(405, ['success' => false, 'message' => 'Metode tidak diizinkan.']);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    authRespond(400, ['success' => false, 'message' => 'Data akun tidak valid.']);
}

$email = strtolower(trim((string) ($input['email'] ?? '')));
$password = (string) ($input['password'] ?? '');
$expectedRole = $input['expectedRole'] ?? 'customer';
if (!in_array($expectedRole, ['customer', 'admin'], true)) {
    $expectedRole = 'customer';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
    authRespond(422, ['success' => false, 'message' => 'Email atau password tidak valid. Password minimal 8 karakter.']);
}

if ($action === 'register') {
    $name = trim((string) ($input['name'] ?? ''));
    $phone = trim((string) ($input['phone'] ?? ''));
    if ($name === '' || strlen($name) > 120) {
        authRespond(422, ['success' => false, 'message' => 'Nama wajib diisi.']);
    }

    try {
        $statement = $pdo->prepare('INSERT INTO users (name, email, phone, password_hash) VALUES (?, ?, ?, ?)');
        $statement->execute([$name, $email, $phone !== '' ? $phone : null, password_hash($password, PASSWORD_DEFAULT)]);
        $userId = (int) $pdo->lastInsertId();
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            authRespond(409, ['success' => false, 'message' => 'Email sudah terdaftar.']);
        }
        authRespond(500, ['success' => false, 'message' => 'Akun gagal dibuat.']);
    }

    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => $userId, 'name' => $name, 'email' => $email, 'role' => 'customer'];
    csrfToken();
    authRespond(201, ['success' => true, 'user' => $_SESSION['user'], 'csrfToken' => $_SESSION['csrf_token']]);
}

if ($action === 'login') {
    try {
        $statement = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        $user = $statement->fetch();
    } catch (PDOException $exception) {
        authRespond(500, ['success' => false, 'message' => 'Tabel akun belum tersedia. Import database.sql atau database_migration_v2.sql terlebih dahulu.']);
    }
    if (!$user || !password_verify($password, $user['password_hash']) || $user['role'] !== $expectedRole) {
        authRespond(401, ['success' => false, 'message' => 'Email atau password salah.']);
    }

    session_regenerate_id(true);
    unset($user['password_hash']);
    $_SESSION['user'] = $user;
    csrfToken();
    authRespond(200, ['success' => true, 'user' => $user, 'csrfToken' => $_SESSION['csrf_token']]);
}

authRespond(400, ['success' => false, 'message' => 'Aksi akun tidak dikenal.']);
