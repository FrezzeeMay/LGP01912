<?php
// config/database.php — Koneksi PDO ke SUPABASE Postgres (online 24 jam).
// Cara pakai: require_once __DIR__ . '/database.php'; lalu gunakan $pdo.
//
// LANGKAH 1 — Ambil data koneksi dari Supabase Dashboard:
//   Buka https://supabase.com/dashboard → project kamu → Settings → Database.
//   Bagian "Connection string" → pilih mode "URI", salin isinya. Contoh:
//     postgresql://postgres.xxxxx:[YOUR-PASSWORD]@aws-0-ap-southeast-1.pooler.supabase.com:6543/postgres
//   Pecah jadi 5 nilai di bawah (HOST, PORT 6543 pooler, DB postgres, USER postgres.xxxxx, PASS).
//
// LANGKAH 2 — Aktifkan driver Postgres di XAMPP (sekali saja):
//   Buka C:\xampp\php\php.ini sebagai Administrator, hapus titik-koma pada:
//     extension=pdo_pgsql
//   Simpan, lalu Restart Apache. Cek: php -m | findstr pgsql
//
// Alternatif tanpa edit file: isi via environment variable
//   SUPABASE_DB_HOST / _PORT / _NAME / _USER / _PASS (lebih aman untuk hosting).

$PG_HOST = getenv('SUPABASE_DB_HOST') ?: 'db.xxxxx.supabase.co';            // <-- GANTI: host pooler
$PG_PORT = getenv('SUPABASE_DB_PORT') ?: '6543';                            // 6543 = pooler (cocok untuk PHP), 5432 = direct
$PG_DB   = getenv('SUPABASE_DB_NAME') ?: 'postgres';
$PG_USER = getenv('SUPABASE_DB_USER') ?: 'postgres.xxxxx';                  // <-- GANTI: user pooler
$PG_PASS = getenv('SUPABASE_DB_PASS') ?: 'ISI-DATABASE-PASSWORD-DISINI';    // <-- GANTI: database password

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
        '<p>Periksa isi <b>config/database.php</b> (HOST/USER/PASSWORD) dan pastikan tabel sudah dibuat via SQL Editor dari <b>database/supabase_schema.sql</b> + <b>database/supabase_seed.sql</b>.</p>' .
        '<p>Detail teknis (sembunyikan di production): ' . htmlspecialchars($e->getMessage()) . '</p>'
    );
}
