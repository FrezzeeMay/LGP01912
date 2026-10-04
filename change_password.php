<?php
// change_password.php — Wajib diganti saat flag must_change_password menyala,
// boleh juga dibuka kapan saja untuk ganti password sendiri.
require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/config/database.php';

$u = current_user();
if (!$u) { header('Location: login.php'); exit; }

$forced = !empty($u['must_change_password']) || isset($_GET['first']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $conf = $_POST['confirm_password'] ?? '';

    $st = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $st->execute([(int)$u['id']]);
    $row = $st->fetch();

    if (!$row || !password_verify($old, $row['password'])) {
        $error = 'Password lama salah.';
    } elseif (strlen($new) < 12) {
        $error = 'Password baru minimal 12 karakter. Gunakan yang panjang & unik.';
    } elseif ($new !== $conf) {
        $error = 'Konfirmasi password tidak sama.';
    } elseif (password_verify($new, $row['password'])) {
        $error = 'Password baru tidak boleh sama dengan yang lama.';
    } else {
        $pdo->prepare('UPDATE users SET password = ?, must_change_password = FALSE WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), (int)$u['id']]);
        $_SESSION['user']['must_change_password'] = false;
        log_activity($pdo, (int)$u['id'], 'ganti_password', 'Mengganti password sendiri');
        flash_set('success', 'Password berhasil diganti. Jaga kerahasiaannya.');
        header('Location: admin/dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ganti Password — KasKita</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body class="login-page">
  <div class="login-wrap" style="grid-template-columns:1fr;max-width:460px">
    <div class="login-card">
      <div class="login-brand"><span class="brand-mark big">K</span>
        <div><h1>KasKita</h1><p>Ganti Password</p></div>
      </div>
      <?php if ($forced): ?>
        <div class="alert info">Demi keamanan, kamu <b>wajib mengganti password awal</b> sebelum bisa memakai sistem.</div>
      <?php endif; ?>
      <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
      <form method="post">
        <label>Password Lama
          <input type="password" name="old_password" required autofocus autocomplete="current-password">
        </label>
        <label>Password Baru (min. 12 karakter)
          <input type="password" name="new_password" required minlength="12" autocomplete="new-password">
        </label>
        <label>Ulangi Password Baru
          <input type="password" name="confirm_password" required minlength="12" autocomplete="new-password">
        </label>
        <button class="btn btn-primary btn-block" type="submit">Simpan Password Baru</button>
      </form>
      <?php if (!$forced): ?>
        <p style="margin-top:12px"><a href="admin/dashboard.php">&larr; Kembali ke dashboard</a></p>
      <?php else: ?>
        <p class="muted small" style="margin-top:12px"><a href="logout.php">Batal &amp; keluar</a></p>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
