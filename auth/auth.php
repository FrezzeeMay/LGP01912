<?php
// auth/auth.php — Helper session, RBAC, CSRF, activity log, formatting.
// Dipakai oleh semua halaman admin & API: require_once __DIR__ . '/../auth/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    // Pengaturan session yang aman untuk XAMPP (HTTP lokal).
    session_start();
}

/** Ambil user yang sedang login dari session, atau null. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Wajib login; jika belum, lempar ke login.php */
function require_login(): array
{
    $u = current_user();
    if (!$u) {
        // Simpan halaman tujuan agar bisa kembali setelah login (opsional).
        $next = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: ../login.php' . ($next ? '?next=' . urlencode($next) : ''));
        if (basename($_SERVER['SCRIPT_NAME']) === 'login.php') return [];
        exit;
    }
    return $u;
}

/**
 * Wajib role tertentu. Validasi HARUS di backend (bukan cuma di frontend).
 * Contoh: require_role(['super_admin']); atau require_role(['admin','super_admin']);
 */
function require_role(array $allowed): array
{
    $u = require_login();
    if (!in_array($u['role'] ?? '', $allowed, true)) {
        http_response_code(403);
        die('<h2>403 — Akses ditolak</h2><p>Role Anda (<b>' . htmlspecialchars($u['role'] ?? '-') . '</b>) tidak diizinkan membuka halaman ini.</p><p><a href="dashboard.php">Kembali ke dashboard</a></p>');
    }
    return $u;
}

/** Boleh menulis (tambah/edit/hapus)? Role 'user' hanya boleh melihat. */
function can_write(?array $u = null): bool
{
    $u = $u ?? current_user();
    return in_array($u['role'] ?? '', ['admin', 'super_admin'], true);
}

/** Tolak request tulis dari role 'user' (dipakai di semua handler POST + API). */
function require_write(?array $u = null): array
{
    $u = $u ?? require_login();
    if (!can_write($u)) {
        http_response_code(403);
        die('<h2>403 — Lihat saja</h2><p>Akun <b>User</b> hanya boleh melihat data, tidak boleh menambah/mengubah/menghapus.</p><p><a href="dashboard.php">Kembali ke dashboard</a></p>');
    }
    return $u;
}

/** Label role untuk badge/nav. */
function role_label(string $role): string
{
    return match ($role) {
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'user' => 'User',
        default => $role,
    };
}

/** Escape output HTML (cegah XSS). */
function e($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Format angka ke Rupiah, cth: 10000 -> Rp10.000 */
function rupiah($n): string
{
    return 'Rp' . number_format((int)$n, 0, ',', '.');
}

/** Format tanggal+jam ke gaya Indonesia: 04/10/2026 10:30 */
function tgl_jam(?string $dt): string
{
    if (!$dt) return '-';
    $ts = strtotime($dt);
    return $ts ? date('d/m/Y H:i', $ts) : e($dt);
}

/** Format tanggal saja: 04/10/2026 */
function tgl(?string $d): string
{
    if (!$d) return '-';
    $ts = strtotime($d);
    return $ts ? date('d/m/Y', $ts) : e($d);
}

/** CSRF: buat token per-session. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** CSRF: verifikasi token dari form/JSON. */
function verify_csrf(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

/** Input field CSRF untuk form HTML. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Catat aktivitas penting ke tabel activity_logs.
 * Dipanggil setelah tambah/edit/hapus pembayaran, peserta, baju, user.
 */
function log_activity(PDO $pdo, int $userId, string $action, string $description): void
{
    try {
        $st = $pdo->prepare('INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)');
        $st->execute([$userId, $action, $description]);
    } catch (Throwable $ex) {
        // Jangan gagalkan transaksi utama hanya karena log gagal.
        error_log('log_activity gagal: ' . $ex->getMessage());
    }
}

/** Ambil display name admin berdasarkan id (untuk tabel). */
function admin_name(PDO $pdo, $id): string
{
    if (!$id) return '-';
    static $cache = [];
    if (isset($cache[$id])) return $cache[$id];
    try {
        $st = $pdo->prepare('SELECT display_name FROM users WHERE id = ?');
        $st->execute([(int)$id]);
        $r = $st->fetch();
        return $cache[$id] = $r['display_name'] ?? ('#' . (int)$id);
    } catch (Throwable $ex) {
        return '-';
    }
}

/** Status pembayaran baju dari nominal yang dibayar vs harga. */
function shirt_pay_status(int $paid, int $price): string
{
    if ($paid <= 0) return 'belum_bayar';
    if ($paid >= $price) return 'lunas';
    return 'sebagian';
}

function shirt_status_label(string $s): string
{
    return match ($s) {
        'lunas' => 'Lunas',
        'sebagian' => 'Sebagian',
        default => 'Belum Bayar',
    };
}

/** Flash message sekali tampil (disimpan di session). */
function flash_set(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}
function flash_get(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}
