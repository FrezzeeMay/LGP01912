<?php
// config/database.php — Koneksi PDO ke SUPABASE Postgres (online 24 jam).
// PENTING: file ini TIDAK berisi password apa pun. Kredensial HANYA dibaca dari:
//   1) file .env di root project (untuk XAMPP/Laragon di komputermu — TIDAK ikut ke GitHub), atau
//   2) Environment Variables (untuk hosting Render/Railway — diisi via dashboard hosting).
// Cara pakai: require_once __DIR__ . '/database.php'; lalu gunakan $pdo.

// --- Mini .env loader (tanpa composer, tanpa library) ---
function kaskita_load_env(string $path): void
{
    if (!is_readable($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || $line[0] === ';') continue;
        $pos = strpos($line, '=');
        if ($pos === false) continue;
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        // Kupas pembungkus kutip "..." atau '...' jika ada.
        if (strlen($val) >= 2) {
            $f = $val[0];
            $l = $val[strlen($val) - 1];
            if (($f === '"' && $l === '"') || ($f === "'" && $l === "'")) {
                $val = substr($val, 1, -1);
            }
        }
        // Environment asli (hosting) menang atas .env — jadi secret hosting tidak tertimpa.
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $val);
            $_ENV[$key] = $val;
        }
    }
}
kaskita_load_env(dirname(__DIR__) . '/.env');

function kaskita_env(string $key, string $default = ''): string
{
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
}

$PG_HOST = kaskita_env('DB_HOST');
$PG_PORT = kaskita_env('DB_PORT', '5432');
$PG_DB   = kaskita_env('DB_NAME', 'postgres');
$PG_USER = kaskita_env('DB_USER');
$PG_PASS = kaskita_env('DB_PASSWORD');

if ($PG_HOST === '' || $PG_USER === '' || $PG_PASS === '') {
    $missing = [];
    if ($PG_HOST === '') $missing[] = 'DB_HOST';
    if ($PG_USER === '') $missing[] = 'DB_USER';
    if ($PG_PASS === '') $missing[] = 'DB_PASSWORD';
    http_response_code(500);
    die(
        '<h2>Database belum dikonfigurasi</h2>' .
        '<p>Key yang kurang: <b>' . htmlspecialchars(implode(', ', $missing)) . '</b>.</p>' .
        '<p>Salin <b>.env.example</b> menjadi <b>.env</b> di root project, lalu isi ' .
        'dengan data dari Supabase Dashboard → Settings → Database → Connection string.</p>' .
        '<p>File <b>.env tidak boleh ikut ke GitHub</b> (sudah masuk .gitignore).</p>'
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
            'Driver pdo_pgsql belum aktif. Buka C:\\xampp\\php\\php.ini sebagai Administrator, ' .
            'hapus titik-koma pada baris extension=pdo_pgsql, simpan, lalu Restart Apache.'
        );
    }
    $pdo = new PDO($dsn, $PG_USER, $PG_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    // Pesan error TIDAK menampilkan password.
    die(
        '<h2>Koneksi Supabase gagal</h2>' .
        '<p>Periksa isi <b>.env</b> (DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASSWORD) dan pastikan ' .
        'tabel sudah dibuat via SQL Editor dari <b>database/supabase_schema.sql</b> + ' .
        '<b>database/supabase_seed.sql</b>.</p>' .
        '<p>Detail teknis (sembunyikan di production): ' . htmlspecialchars($e->getMessage()) . '</p>'
    );
}
