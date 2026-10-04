<?php
// config/database.php — Koneksi PDO ke SUPABASE Postgres (online 24 jam).
// Kredensial TIDAK ada di file ini. Semua dibaca dari file `.env` di root
// project (format KEY=VALUE) atau dari environment variable hosting.
// Cara pakai: require_once __DIR__ . '/database.php'; lalu gunakan $pdo.

// --- 1. Muat .env (tanpa library tambahan; env sistem hosting menang) ---
require_once __DIR__ . '/env.php';
load_env(dirname(__DIR__) . '/.env');

// --- 2. Baca kredensial (DB_* utama, SUPABASE_DB_* sebagai fallback lama) ---
function env2(string $a, string $b, $default = null) {
    $v = getenv($a);
    if ($v !== false && $v !== '') return $v;
    $v = getenv($b);
    if ($v !== false && $v !== '') return $v;
    return $default;
}

$PG_HOST = env2('DB_HOST', 'SUPABASE_DB_HOST');
$PG_PORT = env2('DB_PORT', 'SUPABASE_DB_PORT', '6543');
$PG_DB   = env2('DB_NAME', 'SUPABASE_DB_NAME', 'postgres');
$PG_USER = env2('DB_USER', 'SUPABASE_DB_USER');
$PG_PASS = env2('DB_PASSWORD', 'SUPABASE_DB_PASS');

// --- 3. Validasi: tolak jalan kalau kredensial belum diisi ---
$missing = [];
foreach (['DB_HOST' => $PG_HOST, 'DB_USER' => $PG_USER, 'DB_PASSWORD' => $PG_PASS] as $k => $v) {
    if (!$v || str_contains((string)$v, 'xxxx') || $v === 'password_supabase_kamu') $missing[] = $k;
}
if ($missing) {
    http_response_code(500);
    die(
        '<h2>Kredensial database belum diisi</h2>' .
        '<p>Isi file <b>.env</b> di root project (lihat <b>.env.example</b>). Belum diisi: <b>' .
        htmlspecialchars(implode(', ', $missing)) . '</b></p>'
    );
}

$dsn = "pgsql:host={$PG_HOST};port={$PG_PORT};dbname={$PG_DB};sslmode=require";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    if (!extension_loaded('pdo_pgsql')) {
        throw new PDOException(
            "Driver pdo_pgsql belum aktif. Buka C:\\xampp\\php\\php.ini sebagai Administrator, " .
            "hapus ';' pada baris extension=pdo_pgsql, simpan, lalu Restart Apache."
        );
    }
    $pdo = new PDO($dsn, $PG_USER, $PG_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    die(
        '<h2>Koneksi Supabase gagal</h2>' .
        '<p>Periksa isi <b>.env</b> (HOST/USER/PASSWORD) dan pastikan tabel sudah dibuat via SQL Editor dari <b>database/supabase_schema.sql</b> + <b>database/supabase_seed.sql</b>.</p>' .
        '<p>Detail teknis (sembunyikan di production): ' . htmlspecialchars($e->getMessage()) . '</p>'
    );
}
