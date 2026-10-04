<?php
// admin/participants.php — CRUD peserta. Lihat: semua role. Tulis: super_admin saja.
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/database.php';
$user = require_role(['admin', 'super_admin', 'user']);
$isSuper = ($user['role'] === 'super_admin');
$q = trim($_GET['q'] ?? '');
$SIZES = ['XS','S','M','L','XL','XXL','XXXL'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isSuper) { http_response_code(403); die('Hanya Super Admin yang boleh mengelola peserta.'); }
    if (!verify_csrf($_POST['csrf_token'] ?? null)) { flash_set('error','Token keamanan tidak valid.'); header('Location: participants.php'); exit; }
    $act = $_POST['action'] ?? '';
    if ($act === 'create' || $act === 'update') {
        $name = trim($_POST['name'] ?? '');
        $status = $_POST['status'] ?? 'aktif';
        $size = $_POST['shirt_size'] ?? null;
        if ($name === '' || mb_strlen($name) > 100) flash_set('error','Nama tidak valid (1–100 karakter).');
        elseif (!in_array($status, ['aktif','nonaktif'], true)) flash_set('error','Status tidak valid.');
        elseif ($size !== '' && $size !== null && !in_array($size, $SIZES, true)) flash_set('error','Ukuran baju tidak valid.');
        else {
            $size = ($size === '' ? null : $size);
            if ($act === 'create') {
                $pdo->prepare('INSERT INTO participants (name, status, shirt_size) VALUES (?,?,?)')->execute([$name, $status, $size]);
                log_activity($pdo, (int)$user['id'], 'tambah_peserta', "Menambahkan peserta {$name}");
                flash_set('success', "Peserta {$name} ditambahkan.");
            } else {
                $id = (int)($_POST['id'] ?? 0);
                $pdo->prepare('UPDATE participants SET name=?, status=?, shirt_size=? WHERE id=?')->execute([$name, $status, $size, $id]);
                log_activity($pdo, (int)$user['id'], 'edit_peserta', "Mengedit peserta {$name} (ID #{$id})");
                flash_set('success', 'Peserta diperbarui.');
            }
        }
        header('Location: participants.php'); exit;
    }
    if ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $nm = $pdo->prepare('SELECT name FROM participants WHERE id=?'); $nm->execute([$id]);
        $r = $nm->fetch();
        $pdo->prepare('DELETE FROM participants WHERE id=?')->execute([$id]);
        log_activity($pdo, (int)$user['id'], 'hapus_peserta', 'Menghapus peserta ' . ($r['name'] ?? "#{$id}") . ' beserta seluruh transaksinya');
        flash_set('success', 'Peserta dihapus (transaksi ikut terhapus via CASCADE).');
        header('Location: participants.php'); exit;
    }
}

$sql = "SELECT ps.*, COALESCE((SELECT SUM(amount) FROM payments WHERE participant_id=ps.id),0) AS total FROM participants ps";
$p = [];
if ($q !== '') { $sql .= " WHERE ps.name LIKE ?"; $p[] = "%{$q}%"; }
$sql .= " ORDER BY ps.name ASC";
$st = $pdo->prepare($sql); $st->execute($p);
$rows = $st->fetchAll();

$base='..'; $pageTitle='Peserta'; $pageSub='Orang yang membayar kas. Peserta biasa tidak perlu login.'; $active='participants';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div class="panel-head"><h2>Daftar Peserta (<?= count($rows) ?>)</h2><span class="spacer"></span>
    <form method="get" class="toolbar"><input type="text" name="q" placeholder="Cari nama…" value="<?= e($q) ?>" style="margin:0">
    <?php if ($q!==''): ?><a class="btn btn-ghost btn-sm" href="participants.php">Reset</a><?php endif; ?></form>
    <?php if ($isSuper): ?><button class="btn btn-primary btn-sm" onclick="openModal('Tambah Peserta', document.getElementById('tplAdd').innerHTML)">+ Tambah</button><?php endif; ?>
  </div>
  <?php if (!$isSuper): ?><div class="panel-body" style="padding-top:0"><p class="muted small" style="margin:0">Anda login sebagai Admin — hanya Super Admin yang dapat menambah/mengubah/menghapus peserta.</p></div><?php endif; ?>
  <div class="table-wrap"><table class="grid">
    <thead><tr><th>No</th><th>Nama</th><th>Status</th><th>Ukuran Baju</th><th style="text-align:right">Total Kas</th><?php if ($isSuper): ?><th>Aksi</th><?php endif; ?></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="6" class="empty">Belum ada peserta.</td></tr><?php endif; ?>
    <?php $no=1; foreach ($rows as $r): ?>
      <tr><td><?= $no++ ?></td><td><strong><?= e($r['name']) ?></strong></td>
        <td><?= $r['status']==='aktif' ? '<span class="badge ok">Aktif</span>' : '<span class="badge bad">Nonaktif</span>' ?></td>
        <td><?= e($r['shirt_size'] ?? '—') ?></td>
        <td class="num"><?= rupiah($r['total']) ?></td>
        <?php if ($isSuper): ?><td><div class="row-actions">
          <button class="btn btn-ghost btn-sm" onclick='openEditPeserta(<?= json_encode((int)$r["id"]) ?>,<?= json_encode($r["name"]) ?>,<?= json_encode($r["status"]) ?>,<?= json_encode($r["shirt_size"] ?? "") ?>)'>Edit</button>
          <form method="post" class="inline-form" data-confirm="Hapus <?= e($r['name']) ?> beserta SEMUA transaksinya?">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn btn-danger btn-sm">Hapus</button></form>
        </div></td><?php endif; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<template id="tplAdd">
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="create">
    <label>Nama<input type="text" name="name" maxlength="100" required placeholder="mis. Rayhan"></label>
    <div class="form-grid">
      <label>Status<select name="status"><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option></select></label>
      <label>Ukuran Baju<select name="shirt_size"><option value="">—</option><?php foreach ($SIZES as $s): ?><option><?= $s ?></option><?php endforeach; ?></select></label>
    </div>
    <button class="btn btn-primary btn-block">Simpan</button>
  </form>
</template>
<template id="tplEditP">
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="id" id="epId">
    <label>Nama<input type="text" name="name" id="epName" maxlength="100" required></label>
    <div class="form-grid">
      <label>Status<select name="status" id="epStatus"><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option></select></label>
      <label>Ukuran Baju<select name="shirt_size" id="epSize"><option value="">—</option><?php foreach ($SIZES as $s): ?><option><?= $s ?></option><?php endforeach; ?></select></label>
    </div>
    <button class="btn btn-primary btn-block">Simpan Perubahan</button>
  </form>
</template>
<script>
function openEditPeserta(id, name, status, size){
  openModal('Edit Peserta', document.getElementById('tplEditP').innerHTML);
  var b = document.getElementById('modalBody');
  b.querySelector('#epId').value = id; b.querySelector('#epName').value = name;
  b.querySelector('#epStatus').value = status; b.querySelector('#epSize').value = size || '';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
