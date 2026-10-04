<?php
// admin/users.php — Kelola akun admin. KHUSUS super_admin (RBAC ditegakkan di backend).
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/database.php';
$user = require_role(['super_admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) { flash_set('error','Token keamanan tidak valid.'); header('Location: users.php'); exit; }
    $act = $_POST['action'] ?? '';
    if ($act === 'create' || $act === 'update') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $dname = trim($_POST['display_name'] ?? '');
        $role = $_POST['role'] ?? 'admin';
        $pass = $_POST['password'] ?? '';
        $valid = preg_match('/^[A-Za-z0-9_]{3,50}$/', $username) && filter_var($email, FILTER_VALIDATE_EMAIL)
              && $dname !== '' && mb_strlen($dname) <= 100 && in_array($role, ['admin','super_admin','user'], true);
        if (!$valid) flash_set('error','Input tidak valid. Username 3–50 karakter (huruf/angka/_), email valid.');
        elseif ($act === 'create' && strlen($pass) < 8) flash_set('error','Password minimal 8 karakter.');
        elseif ($act === 'update' && $pass !== '' && strlen($pass) < 8) flash_set('error','Password minimal 8 karakter (kosongkan jika tidak diubah).');
        else {
            try {
                if ($act === 'create') {
                    $pdo->prepare('INSERT INTO users (username,email,password,display_name,role,must_change_password) VALUES (?,?,?,?,?,TRUE)')
                        ->execute([$username, $email, password_hash($pass, PASSWORD_DEFAULT), $dname, $role]);
                    log_activity($pdo, (int)$user['id'], 'tambah_admin', "Menambahkan akun {$username} ({$role})");
                    flash_set('success', "Akun {$username} dibuat. Minta pemiliknya ganti password saat login pertama (dipaksa sistem).");
                } else {
                    $id = (int)($_POST['id'] ?? 0);
                    // Jika admin me-reset password orang lain, paksa ganti lagi saat login berikut.
                    if ($pass !== '') $pdo->prepare('UPDATE users SET username=?,email=?,password=?,display_name=?,role=?,must_change_password=TRUE WHERE id=?')
                        ->execute([$username,$email,password_hash($pass,PASSWORD_DEFAULT),$dname,$role,$id]);
                    else $pdo->prepare('UPDATE users SET username=?,email=?,display_name=?,role=? WHERE id=?')
                        ->execute([$username,$email,$dname,$role,$id]);
                    log_activity($pdo, (int)$user['id'], 'edit_admin', "Mengedit akun {$username} (ID #{$id})");
                    flash_set('success', 'Akun diperbarui.');
                }
            } catch (PDOException $ex) {
                flash_set('error', 'Gagal menyimpan: username/email kemungkinan sudah dipakai.');
            }
        }
        header('Location: users.php'); exit;
    }
    if ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$user['id']) flash_set('error','Tidak bisa menghapus akun sendiri.');
        else {
            $nm = $pdo->prepare('SELECT username FROM users WHERE id=?'); $nm->execute([$id]);
            $r = $nm->fetch();
            $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
            log_activity($pdo, (int)$user['id'], 'hapus_admin', 'Menghapus akun ' . ($r['username'] ?? "#{$id}"));
            flash_set('success', 'Akun dihapus.');
        }
        header('Location: users.php'); exit;
    }
}

$rows = $pdo->query('SELECT id, username, email, display_name, role, created_at FROM users ORDER BY id ASC')->fetchAll();
$base='..'; $pageTitle='Kelola Admin'; $pageSub='Hanya Super Admin. Password selalu disimpan sebagai hash.'; $active='users';
include __DIR__ . '/../includes/header.php';
?>
<div class="panel">
  <div class="panel-head"><h2>Akun (<?= count($rows) ?>)</h2><span class="spacer"></span>
    <button class="btn btn-primary btn-sm" onclick="openModal('Tambah Akun', document.getElementById('tplUAdd').innerHTML)">+ Tambah Akun</button></div>
  <div class="table-wrap"><table class="grid">
    <thead><tr><th>ID</th><th>Nama Tampil</th><th>Username / Email</th><th>Role</th><th>Dibuat</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr><td><?= (int)$r['id'] ?></td><td><strong><?= e($r['display_name']) ?></strong></td>
        <td><code><?= e($r['username']) ?></code><br><span class="muted small"><?= e($r['email']) ?></span></td>
        <td><?= $r['role']==='super_admin' ? '<span class="badge warn">Super Admin</span>' : ($r['role']==='user' ? '<span class="badge neutral">User (lihat saja)</span>' : '<span class="badge info">Admin</span>') ?></td>
        <td><?= tgl($r['created_at']) ?></td>
        <td><div class="row-actions">
          <button class="btn btn-ghost btn-sm" onclick='openEditUser(<?= json_encode((int)$r["id"]) ?>,<?= json_encode($r["username"]) ?>,<?= json_encode($r["email"]) ?>,<?= json_encode($r["display_name"]) ?>,<?= json_encode($r["role"]) ?>)'>Edit</button>
          <?php if ((int)$r['id'] !== (int)$user['id']): ?>
          <form method="post" class="inline-form" data-confirm="Hapus akun <?= e($r['username']) ?>?">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn btn-danger btn-sm">Hapus</button></form>
          <?php endif; ?>
        </div></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<template id="tplUAdd">
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="create">
    <div class="form-grid">
      <label>Username<input type="text" name="username" required pattern="[A-Za-z0-9_]{3,50}"></label>
      <label>Nama Tampil<input type="text" name="display_name" required maxlength="100"></label>
    </div>
    <label>Email<input type="email" name="email" required></label>
    <div class="form-grid">
      <label>Password<input type="password" name="password" required minlength="8"></label>
      <label>Role<select name="role"><option value="admin">Admin</option><option value="user">User (lihat saja)</option><option value="super_admin">Super Admin</option></select></label>
    </div>
    <button class="btn btn-primary btn-block">Buat Akun</button>
  </form>
</template>
<template id="tplUEdit">
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="id" id="euId">
    <div class="form-grid">
      <label>Username<input type="text" name="username" id="euU" required></label>
      <label>Nama Tampil<input type="text" name="display_name" id="euD" required></label>
    </div>
    <label>Email<input type="email" name="email" id="euE" required></label>
    <div class="form-grid">
      <label>Password baru (kosongkan jika tidak diubah)<input type="password" name="password" minlength="8"></label>
      <label>Role<select name="role" id="euR"><option value="admin">Admin</option><option value="user">User (lihat saja)</option><option value="super_admin">Super Admin</option></select></label>
    </div>
    <button class="btn btn-primary btn-block">Simpan</button>
  </form>
</template>
<script>
function openEditUser(id,u,e2,d,r){openModal('Edit Akun',document.getElementById('tplUEdit').innerHTML);
var b=document.getElementById('modalBody');b.querySelector('#euId').value=id;b.querySelector('#euU').value=u;
b.querySelector('#euE').value=e2;b.querySelector('#euD').value=d;b.querySelector('#euR').value=r;}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
