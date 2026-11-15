<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();

header('Content-Type: application/json; charset=utf-8');

function login_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    login_response(405, ['error' => 'Method not allowed.']);
}

$payload = json_decode(file_get_contents('php://input'), true);
$username = trim((string)($payload['username'] ?? ''));
$password = (string)($payload['password'] ?? '');

if ($username === '' || $password === '') {
    login_response(422, ['error' => 'Admin username and password are required.']);
}

require_once __DIR__ . '/../config/db.php';
$stmt = $conn->prepare('SELECT id, username, password FROM admins WHERE username=?');
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$valid = $admin &&
    (password_verify($password, $admin['password']) ||
        hash_equals($admin['password'], hash('sha256', $password)));

if (!$valid) {
    login_response(401, ['error' => 'Invalid library admin credentials.']);
}

session_regenerate_id(true);
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = $admin['username'];
$_SESSION['admin_id'] = (int)$admin['id'];

login_response(200, [
    'authenticated' => true,
    'csrf_token' => csrf_token(),
]);
