<?php
// admin/shirts.php — Project baju gabungan: foto, target otomatis, progress, ukuran, status bayar.
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/database.php';
$user = require_role(['admin', 'super_admin', 'user']);
$isSuper = ($user['role'] === 'super_admin');
$canWrite = can_write($user); // role 'user' hanya melihat
$SIZES = ['XS','S','M','L','XL','XXL','XXXL'];
$UPLOAD_DIR = __DIR__ . '/../uploads/shirts/';
$ALLOWED_EXT = ['jpg','jpeg','png','webp'];
$ALLOWED_MIME = ['image/jpeg','image/png','image/webp'];

/** Upload foto baju, return path relatif (mis. uploads/shirts/abc.jpg) atau null. */
function save_shirt_image(array $f, array $ALLOWED_EXT, array $ALLOWED_MIME, string $UPLOAD_DIR): ?string
{
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($f['error'] ?? 0) !== UPLOAD_ERR_OK) throw new RuntimeException('Upload gagal (kode ' . $f['error'] . ').');
    if (($f['size'] ?? 0) > 2 * 1024 * 1024) throw new RuntimeException('Ukuran file maksimal 2 MB.');
    $ext = strtolower(pathinfo($f['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, $ALLOWED_EXT, true)) throw new RuntimeException('Format harus JPG/JPEG/PNG/WebP.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($f['tmp_name']);
    if (!in_array($mime, $ALLOWED_MIME, true)) throw new RuntimeException('File bukan gambar yang valid.');
    if (!is_dir($UPLOAD_DIR)) mkdir($UPLOAD_DIR, 0755, true);
    $name = 'baju_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $UPLOAD_DIR . $name)) throw new RuntimeException('Gagal menyimpan file upload.');
    return 'uploads/shirts/' . $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) { flash_set('error','Token keamanan tidak valid.'); header('Location: shirts.php'); exit; }
    $act = $_POST['action'] ?? '';
    try {
        // ---- Project: hanya super_admin yang boleh buat/ubah/hapus project & target ----
        if (in_array($act, ['project_create','project_update','project_delete'], true) && !$isSuper) {
            throw new RuntimeException('Hanya Super Admin yang boleh mengelola project/target baju.');
        }
        if ($act === 'project_create' || $act === 'project_update') {
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $price = (int)($_POST['price_per_person'] ?? 0);
            $count = (int)($_POST['participant_count'] ?? 0);
            $status = $_POST['status'] ?? 'berjalan';
            if ($name === '' || mb_strlen($name) > 150) throw new RuntimeException('Nama project 1–150 karakter.');
            if ($price < 0 || $price > 100000000) throw new RuntimeException('Harga tidak valid.');
            if ($count < 0 || $count > 10000) throw new RuntimeException('Jumlah peserta tidak valid.');
            if (!in_array($status, ['berjalan','selesai','batal'], true)) throw new RuntimeException('Status tidak valid.');
            $target = $price * $count; // OTOMATIS, admin tidak menghitung manual.
            $imgPath = null;
            if (!empty($_FILES['image']['name'] ?? '')) $imgPath = save_shirt_image($_FILES['image'], $ALLOWED_EXT, $ALLOWED_MIME, $UPLOAD_DIR);
            if ($act === 'project_create') {
                $pdo->prepare('INSERT INTO shirt_projects (name,image,description,price_per_person,participant_count,target_amount,status) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$name, $imgPath, $desc !== '' ? $desc : null, $price, $count, $target, $status]);
                log_activity($pdo, (int)$user['id'], 'buat_project_baju', "Membuat project baju \"{$name}\" (target " . rupiah($target) . ")");
                flash_set('success', "Project {$name} dibuat. Target otomatis " . rupiah($target) . ".");
            } else {
                $id = (int)($_POST['project_id'] ?? 0);
                $old = $pdo->prepare('SELECT image FROM shirt_projects WHERE id=?'); $old->execute([$id]);
                $o = $old->fetch();
                if ($imgPath && !empty($o['image']) && file_exists(__DIR__ . '/../' . $o['image'])) @unlink(__DIR__ . '/../' . $o['image']);
                if ($imgPath) $pdo->prepare('UPDATE shirt_projects SET name=?,image=?,description=?,price_per_person=?,participant_count=?,target_amount=?,status=? WHERE id=?')
                        ->execute([$name,$imgPath,$desc !== '' ? $desc : null,$price,$count,$target,$status,$id]);
                else $pdo->prepare('UPDATE shirt_projects SET name=?,description=?,price_per_person=?,participant_count=?,target_amount=?,status=? WHERE id=?')
                        ->execute([$name,$desc !== '' ? $desc : null,$price,$count,$target,$status,$id]);
                // Sinkronkan status lunas anggota yang harga berubah:
                $pdo->prepare("UPDATE shirt_participants sp JOIN shirt_projects p ON p.id=sp.project_id SET sp.status = CASE WHEN sp.amount_paid<=0 THEN 'belum_bayar' WHEN sp.amount_paid>=p.price_per_person THEN 'lunas' ELSE 'sebagian' END WHERE sp.project_id=?")->execute([$id]);
                log_activity($pdo, (int)$user['id'], 'edit_project_baju', "Mengedit project \"{$name}\" (target " . rupiah($target) . ")");
                flash_set('success', 'Project diperbarui. Target & status anggota dihitung ulang.');
            }
            header('Location: shirts.php'); exit;
        }
        if ($act === 'project_delete') {
            $id = (int)($_POST['project_id'] ?? 0);
            $nm = $pdo->prepare('SELECT name,image FROM shirt_projects WHERE id=?'); $nm->execute([$id]);
            $r = $nm->fetch();
            $pdo->prepare('DELETE FROM shirt_projects WHERE id=?')->execute([$id]);
            if (!empty($r['image']) && file_exists(__DIR__ . '/../' . $r['image'])) @unlink(__DIR__ . '/../' . $r['image']);
            log_activity($pdo, (int)$user['id'], 'hapus_project_baju', 'Menghapus project baju ' . ($r['name'] ?? "#{$id}"));
            flash_set('success', 'Project dihapus.'); header('Location: shirts.php'); exit;
        }
        // ---- Anggota & setoran: admin & super_admin boleh; user ditolak ----
        if (in_array($act, ['member_add','member_update','member_pay','member_delete'], true) && !$canWrite) {
            throw new RuntimeException('Akun User hanya boleh melihat, tidak boleh mengubah data baju.');
        }
        if ($act === 'member_add') {
            $pid = (int)($_POST['project_id'] ?? 0);
            $part = (int)($_POST['participant_id'] ?? 0);
            $size = $_POST['size'] ?? 'L';
            $paid = (int)($_POST['amount_paid'] ?? 0);
            if (!in_array($size, $SIZES, true)) throw new RuntimeException('Ukuran tidak valid.');
            if ($paid < 0 || $paid > 100000000) throw new RuntimeException('Nominal tidak valid.');
            $pr = $pdo->prepare('SELECT price_per_person FROM shirt_projects WHERE id=?'); $pr->execute([$pid]);
            $proj = $pr->fetch();
            if (!$proj) throw new RuntimeException('Project tidak ditemukan.');
            $stt = shirt_pay_status($paid, (int)$proj['price_per_person']);
            $pdo->prepare('INSERT INTO shirt_participants (project_id, participant_id, size, amount_paid, status) VALUES (?,?,?,?,?) ON CONFLICT (project_id, participant_id) DO UPDATE SET size=EXCLUDED.size, amount_paid=EXCLUDED.amount_paid, status=EXCLUDED.status')->execute([$pid,$part,$size,$paid,$stt]);
            $pdo->prepare('UPDATE participants SET shirt_size=? WHERE id=?')->execute([$size,$part]);
            $nm = $pdo->prepare('SELECT name FROM participants WHERE id=?'); $nm->execute([$part]); $pn = $nm->fetch()['name'] ?? "#{$part}";
            log_activity($pdo, (int)$user['id'], 'tambah_anggota_baju', "Menambahkan {$pn} (size {$size}) ke project #{$pid} — " . rupiah($paid));
            flash_set('success', "{$pn} ditambahkan ke project."); header('Location: shirts.php'); exit;
        }
        if ($act === 'member_update') {
            $mid = (int)($_POST['member_id'] ?? 0);
            $size = $_POST['size'] ?? 'L';
            $paid = (int)($_POST['amount_paid'] ?? 0);
            if (!in_array($size, $SIZES, true)) throw new RuntimeException('Ukuran tidak valid.');
            if ($paid < 0 || $paid > 100000000) throw new RuntimeException('Nominal tidak valid.');
            $m = $pdo->prepare('SELECT sp.*, p.price_per_person, ps.name FROM shirt_participants sp JOIN shirt_projects p ON p.id=sp.project_id JOIN participants ps ON ps.id=sp.participant_id WHERE sp.id=?');
            $m->execute([$mid]); $row = $m->fetch();
            if (!$row) throw new RuntimeException('Anggota tidak ditemukan.');
            $stt = shirt_pay_status($paid, (int)$row['price_per_person']);
            $pdo->prepare('UPDATE shirt_participants SET size=?, amount_paid=?, status=? WHERE id=?')->execute([$size,$paid,$stt,$mid]);
            $pdo->prepare('UPDATE participants SET shirt_size=? WHERE id=?')->execute([$size,(int)$row['participant_id']]);
            log_activity($pdo, (int)$user['id'], 'edit_anggota_baju', "Mengedit {$row['name']}: size {$size}, bayar " . rupiah($paid) . " (" . shirt_status_label($stt) . ")");
            flash_set('success', 'Data anggota diperbarui.'); header('Location: shirts.php'); exit;
        }
        if ($act === 'member_pay') {
            $mid = (int)($_POST['member_id'] ?? 0);
            $add = (int)($_POST['add_amount'] ?? 0);
            if ($add <= 0 || $add > 100000000) throw new RuntimeException('Nominal setoran tidak valid.');
            $m = $pdo->prepare('SELECT sp.*, p.price_per_person, ps.name FROM shirt_participants sp JOIN shirt_projects p ON p.id=sp.project_id JOIN participants ps ON ps.id=sp.participant_id WHERE sp.id=?');
            $m->execute([$mid]); $row = $m->fetch();
            if (!$row) throw new RuntimeException('Anggota tidak ditemukan.');
            $new = (int)$row['amount_paid'] + $add;
            $stt = shirt_pay_status($new, (int)$row['price_per_person']);
            $pdo->prepare('UPDATE shirt_participants SET amount_paid=?, status=? WHERE id=?')->execute([$new,$stt,$mid]);
            log_activity($pdo, (int)$user['id'], 'bayar_baju', "Mencatat setoran baju " . rupiah($add) . " dari {$row['name']} (total " . rupiah($new) . ")");
            flash_set('success', "Setoran " . rupiah($add) . " dari {$row['name']} tercatat."); header('Location: shirts.php'); exit;
        }
        if ($act === 'member_delete') {
            $mid = (int)($_POST['member_id'] ?? 0);
            $m = $pdo->prepare('SELECT sp.*, ps.name FROM shirt_participants sp JOIN participants ps ON ps.id=sp.participant_id WHERE sp.id=?');
            $m->execute([$mid]); $row = $m->fetch();
            $pdo->prepare('DELETE FROM shirt_participants WHERE id=?')->execute([$mid]);
            log_activity($pdo, (int)$user['id'], 'hapus_anggota_baju', 'Menghapus ' . ($row['name'] ?? "#{$mid}") . ' dari project baju');
            flash_set('success', 'Anggota dihapus dari project.'); header('Location: shirts.php'); exit;
        }
    } catch (RuntimeException $ex) {
        flash_set('error', $ex->getMessage()); header('Location: shirts.php'); exit;
    } catch (PDOException $ex) {
        flash_set('error', 'Database error: ' . $ex->getMessage()); header('Location: shirts.php'); exit;
    }
}

// ---------- TAMPIL ----------
$projects = $pdo->query('SELECT * FROM shirt_projects ORDER BY id DESC')->fetchAll();
$membersByProject = [];
if ($projects) {
    $ids = array_column($projects, 'id');
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT sp.*, ps.name AS pname FROM shirt_participants sp JOIN participants ps ON ps.id=sp.participant_id WHERE sp.project_id IN ($ph) ORDER BY ps.name ASC");
    $st->execute($ids);
    foreach ($st->fetchAll() as $m) $membersByProject[$m['project_id']][] = $m;
}
$allPeserta = $pdo->query("SELECT id, name FROM participants WHERE status='aktif' ORDER BY name ASC")->fetchAll();

$base='..'; $pageTitle='Baju Gabungan'; $pageSub='Target = harga × peserta (otomatis). Progress & status bayar dihitung dari database.'; $active='shirts';
$extraJs = ['assets/js/shirts.js'];
include __DIR__ . '/../includes/header.php';
?>

<?php if ($isSuper): ?>
<div class="panel">
  <div class="panel-head"><h2>Buat Project Baju</h2></div>
  <div class="panel-body">
    <form method="post" enctype="multipart/form-data" data-calc-target>
      <?= csrf_field() ?><input type="hidden" name="action" value="project_create">
      <div class="form-grid">
        <label>Nama Project<input type="text" name="name" required maxlength="150" placeholder="Baju Angkatan 2026"></label>
        <label>Foto / Desain Baju (JPG/PNG/WebP, maks 2 MB)<input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" data-preview="prevNew"></label>
      </div>
      <img id="prevNew" alt="" style="display:none;max-width:220px;border-radius:12px;margin-top:8px;border:1px solid var(--line)">
      <label>Deskripsi<textarea name="description" placeholder="Bahan, warna, jadwal produksi…"></textarea></label>
      <div class="form-grid" style="grid-template-columns:repeat(3,1fr)">
        <label>Harga per Orang (Rp)<input type="number" name="price_per_person" min="0" value="75000" required></label>
        <label>Jumlah Peserta<input type="number" name="participant_count" min="0" value="10" required></label>
        <label>Status<select name="status"><option value="berjalan">Berjalan</option><option value="selesai">Selesai</option><option value="batal">Batal</option></select></label>
      </div>
      <p>Target Keseluruhan (otomatis): <strong data-target-out>Rp0</strong></p>
      <button class="btn btn-primary">Buat Project</button>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if (!$projects): ?><div class="panel"><div class="panel-body empty">Belum ada project baju.</div></div><?php endif; ?>

<?php foreach ($projects as $pj):
  $members = $membersByProject[$pj['id']] ?? [];
  $collected = array_sum(array_column($members, 'amount_paid'));
  $target = (int)$pj['target_amount'];
  $pct = $target > 0 ? min(100, $collected / $target * 100) : 0;
  $kurang = max(0, $target - $collected);
  $imgSrc = !empty($pj['image']) ? ('../' . $pj['image']) : null;
?>
<div class="panel">
  <div class="panel-head"><h2><?= e($pj['name']) ?></h2>
    <span class="badge <?= $pj['status']==='berjalan'?'info':($pj['status']==='selesai'?'ok':'bad') ?>"><?= e(ucfirst($pj['status'])) ?></span>
    <span class="spacer"></span>
    <?php if ($isSuper): ?>
      <button class="btn btn-ghost btn-sm" onclick='openEditProject(<?= json_encode((int)$pj["id"]) ?>,<?= json_encode($pj["name"]) ?>,<?= json_encode($pj["description"] ?? "") ?>,<?= json_encode((int)$pj["price_per_person"]) ?>,<?= json_encode((int)$pj["participant_count"]) ?>,<?= json_encode($pj["status"]) ?>)'>Edit Project</button>
      <form method="post" class="inline-form" data-confirm="Hapus project <?= e($pj['name']) ?> beserta semua anggotanya?"><?= csrf_field() ?><input type="hidden" name="action" value="project_delete"><input type="hidden" name="project_id" value="<?= (int)$pj['id'] ?>"><button class="btn btn-danger btn-sm">Hapus</button></form>
    <?php endif; ?>
  </div>
  <div class="panel-body">
    <div class="shirt-grid">
      <div>
        <?php if ($imgSrc && file_exists(__DIR__ . '/../' . $pj['image'])): ?>
          <img class="shirt-photo" src="<?= e($imgSrc) ?>" alt="Desain <?= e($pj['name']) ?>">
        <?php else: ?>
          <div class="empty" style="border:1px dashed var(--line);border-radius:14px">Belum ada foto desain.</div>
        <?php endif; ?>
        <?php if (!empty($pj['description'])): ?><p class="muted small"><?= e($pj['description']) ?></p><?php endif; ?>
      </div>
      <div>
        <dl class="kv">
          <dt>Harga / orang</dt><dd><?= rupiah($pj['price_per_person']) ?></dd>
          <dt>Peserta target</dt><dd><?= (int)$pj['participant_count'] ?> orang</dd>
          <dt>Target</dt><dd><?= rupiah($target) ?></dd>
          <dt>Terkumpul</dt><dd><?= rupiah($collected) ?> (<?= number_format($pct, 2) ?>%)</dd>
        </dl>
        <div class="progress"><i style="width:<?= number_format($pct,2) ?>%"></i></div>
        <p><?= $kurang <= 0 && $target > 0 ? '<strong>Target tercapai!</strong>' : 'Kurang <strong>' . rupiah($kurang) . '</strong> lagi' ?></p>

        <h3 style="margin-top:14px">Anggota (<?= count($members) ?>)</h3>
        <?php if ($canWrite): ?>
        <form method="post" class="toolbar">
          <?= csrf_field() ?><input type="hidden" name="action" value="member_add"><input type="hidden" name="project_id" value="<?= (int)$pj['id'] ?>">
          <div class="grow"><label style="margin:0">Peserta<select name="participant_id" required><option value="">— pilih —</option><?php foreach ($allPeserta as $ap): ?><option value="<?= (int)$ap['id'] ?>"><?= e($ap['name']) ?></option><?php endforeach; ?></select></label></div>
          <div><label style="margin:0">Ukuran<select name="size"><?php foreach ($SIZES as $s): ?><option <?= $s==='L'?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select></label></div>
          <div><label style="margin:0">Sudah Bayar (Rp)<input type="number" name="amount_paid" min="0" value="0"></label></div>
          <div><label style="margin:0">&nbsp;<button class="btn btn-primary btn-sm" style="width:100%">Tambah</button></label></div>
        </form>
        <?php endif; ?>

        <div class="table-wrap" style="margin-top:10px"><table class="grid">
          <thead><tr><th>No</th><th>Nama</th><th>Ukuran</th><th style="text-align:right">Harga</th><th style="text-align:right">Dibayar</th><th>Status</th><?php if ($canWrite): ?><th>Aksi</th><?php endif; ?></tr></thead>
          <tbody>
          <?php if (!$members): ?><tr><td colspan="7" class="empty">Belum ada anggota.</td></tr><?php endif; ?>
          <?php $no=1; foreach ($members as $m):
            $sisa = max(0, (int)$pj['price_per_person'] - (int)$m['amount_paid']);
            $badge = $m['status']==='lunas' ? 'ok' : ($m['status']==='sebagian' ? 'warn' : 'bad');
          ?>
            <tr><td><?= $no++ ?></td><td><strong><?= e($m['pname']) ?></strong></td><td><?= e($m['size']) ?></td>
              <td class="num"><?= rupiah($pj['price_per_person']) ?></td>
              <td class="num"><?= rupiah($m['amount_paid']) ?><br><span class="muted small">sisa <?= rupiah($sisa) ?></span></td>
              <td><span class="badge <?= $badge ?>"><?= e(shirt_status_label($m['status'])) ?></span></td>
              <?php if ($canWrite): ?><td><div class="row-actions">
                <button class="btn btn-ghost btn-sm" onclick='openPay(<?= (int)$m["id"] ?>,<?= json_encode($m["pname"]) ?>)'>+ Setor</button>
                <button class="btn btn-ghost btn-sm" onclick='openEditMember(<?= (int)$m["id"] ?>,<?= json_encode($m["size"]) ?>,<?= json_encode((int)$m["amount_paid"]) ?>)'>Edit</button>
                <form method="post" class="inline-form" data-confirm="Hapus <?= e($m['pname']) ?> dari project ini?"><?= csrf_field() ?><input type="hidden" name="action" value="member_delete"><input type="hidden" name="member_id" value="<?= (int)$m['id'] ?>"><button class="btn btn-danger btn-sm">Hapus</button></form>
              </div></td><?php endif; ?></tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<template id="tplEditProject">
  <form method="post" enctype="multipart/form-data" data-calc-target>
    <?= csrf_field() ?><input type="hidden" name="action" value="project_update"><input type="hidden" name="project_id" id="xpId">
    <label>Nama Project<input type="text" name="name" id="xpName" required maxlength="150"></label>
    <label>Ganti Foto (opsional)<input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"></label>
    <label>Deskripsi<textarea name="description" id="xpDesc"></textarea></label>
    <div class="form-grid" style="grid-template-columns:repeat(3,1fr)">
      <label>Harga<input type="number" name="price_per_person" id="xpPrice" min="0" required></label>
      <label>Jumlah Peserta<input type="number" name="participant_count" id="xpCount" min="0" required></label>
      <label>Status<select name="status" id="xpStatus"><option value="berjalan">Berjalan</option><option value="selesai">Selesai</option><option value="batal">Batal</option></select></label>
    </div>
    <p>Target otomatis: <strong data-target-out>Rp0</strong></p>
    <button class="btn btn-primary btn-block">Simpan</button>
  </form>
</template>
<template id="tplEditMember">
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="member_update"><input type="hidden" name="member_id" id="xmId">
    <div class="form-grid">
      <label>Ukuran<select name="size" id="xmSize"><?php foreach ($SIZES as $s): ?><option><?= $s ?></option><?php endforeach; ?></select></label>
      <label>Total Dibayar (Rp)<input type="number" name="amount_paid" id="xmPaid" min="0" required></label>
    </div>
    <button class="btn btn-primary btn-block">Simpan</button>
  </form>
</template>
<template id="tplPay">
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="member_pay"><input type="hidden" name="member_id" id="xpayId">
    <p class="muted" id="xpayInfo"></p>
    <label>Nominal Setoran (Rp)<input type="number" name="add_amount" min="1" required placeholder="25000"></label>
    <button class="btn btn-primary btn-block">Catat Setoran</button>
  </form>
</template>
<script>
function openEditProject(id,n,d,price,count,st){openModal('Edit Project',document.getElementById('tplEditProject').innerHTML);
var b=document.getElementById('modalBody');b.querySelector('#xpId').value=id;b.querySelector('#xpName').value=n;
b.querySelector('#xpDesc').value=d||'';b.querySelector('#xpPrice').value=price;b.querySelector('#xpCount').value=count;
b.querySelector('#xpStatus').value=st;
var pr=b.querySelector('[name=price_per_person]'),ct=b.querySelector('[name=participant_count]'),out=b.querySelector('[data-target-out]');
var f=function(){out.textContent='Rp'+(Number(pr.value||0)*Number(ct.value||0)).toLocaleString('id-ID')};
pr.oninput=f;ct.oninput=f;f();}
function openEditMember(id,size,paid){openModal('Edit Anggota',document.getElementById('tplEditMember').innerHTML);
var b=document.getElementById('modalBody');b.querySelector('#xmId').value=id;b.querySelector('#xmSize').value=size;b.querySelector('#xmPaid').value=paid;}
function openPay(id,name){openModal('Setoran Baju',document.getElementById('tplPay').innerHTML);
var b=document.getElementById('modalBody');b.querySelector('#xpayId').value=id;b.querySelector('#xpayInfo').textContent='Setoran untuk '+name+'. Status (Belum/Sebagian/Lunas) dihitung otomatis.';}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
