<?php
// API JSON pembayaran — dipakai fetch() dari frontend; admin pages tetap punya fallback form biasa.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../config/database.php';

function jres($ok, $msg, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'message' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}
$u = current_user();
if (!$u) jres(false, 'Belum login.', null, 401);
if (!in_array($u['role'] ?? '', ['admin', 'super_admin', 'user'], true)) jres(false, 'Akses ditolak.', null, 403);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jres(false, 'Metode harus POST.', null, 405);
$in = $_POST + (json_decode(file_get_contents('php://input'), true) ?? []);
if (!verify_csrf($in['csrf_token'] ?? null)) jres(false, 'Token CSRF tidak valid.', null, 419);
// Endpoint tulis harus menambahkan: if (!can_write($u)) jres(false, 'Akun User hanya boleh melihat.', null, 403);
