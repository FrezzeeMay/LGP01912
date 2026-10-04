<?php
// login.php — halaman login modern (session PHP + password_verify).
require_once __DIR__ . '/auth/auth.php';

if (current_user()) {
    header('Location: admin/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');      // username ATAU email
    $pass  = $_POST['password'] ?? '';

    if ($login === '' || $pass === '') {
        $error = 'Isi username/email dan password.';
    } else {
        require_once __DIR__ . '/config/database.php';
        // Cari berdasarkan username atau email (case-insensitive).
        $st = $pdo->prepare('SELECT * FROM users WHERE LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?) LIMIT 1');
        $st->execute([$login, $login]);
        $u = $st->fetch();

        if ($u && password_verify($pass, $u['password'])) {
            // Regenerasi session id (cegah session fixation).
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int)$u['id'],
                'username' => $u['username'],
                'email' => $u['email'],
                'display_name' => $u['display_name'],
                'role' => $u['role'],
            ];
            try {
                $pdo->prepare('INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)')
                    ->execute([(int)$u['id'], 'login', 'Login ke sistem']);
            } catch (Throwable $ex) {}
            $next = $_GET['next'] ?? 'admin/dashboard.php';
            // Cegah open-redirect: hanya path lokal.
            if (!str_starts_with($next, 'admin/')) $next = 'admin/dashboard.php';
            header('Location: ' . $next);
            exit;
        }
        $error = 'Username/email atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk — KasKita</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body class="login-page">
  <div class="login-wrap">
    <div class="login-card">
      <div class="login-brand"><span class="brand-mark big">K</span>
        <div><h1>KasKita</h1><p>Sistem Kas &amp; Pembuatan Baju</p></div>
      </div>
      <h2>Masuk ke dashboard</h2>
      <p class="muted">Gunakan akun Admin A, Admin B, atau Super Admin.</p>
      <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
      <form method="post" autocomplete="on">
        <label>Username / Email
          <input type="text" name="login" required autofocus placeholder="mis. admin_a" value="<?= e($_POST['login'] ?? '') ?>">
        </label>
        <label>Password
          <input type="password" name="password" required placeholder="••••••••">
        </label>
        <button class="btn btn-primary btn-block" type="submit">Masuk</button>
      </form>
      <details class="demo">
        <summary>Akun demo (dari supabase_seed.sql)</summary>
        <ul>
          <li><code>alpi019@gmail.com</code> / <code>LGP019</code> — Admin (Alpi)</li>
          <li><code>naufal019@gmail.com</code> / <code>LGP019</code> — Admin (Naufal)</li>
          <li><code>Rayhan019@gmai.com</code> / <code>Apalah019</code> — Super Admin</li>
          <li><code>viewer@kaskita.local</code> / <code>Viewer123!</code> — User (lihat saja)</li>
        </ul>
        <p class="muted small">Bisa login pakai username juga (mis. <code>alpi</code>).</p>
      </details>
    </div>
    <div class="login-side">
      <h3>Kas kelas yang rapi,<br>baju angkatan yang terkumpul.</h3>
      <ul>
        <li>Setiap transaksi tercatat + nama admin penginput.</li>
        <li>Total peserta &amp; total kas dihitung otomatis.</li>
        <li>Target &amp; progress baju terhitung sendiri.</li>
        <li>Audit log: jelas siapa mengubah apa.</li>
      </ul>
    </div>
  </div>
</body>
</html>
