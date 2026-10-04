<?php
// admin/payments.php — Tabel utama kas + tambah/edit/hapus + riwayat modal + search/filter.
// PUBLIK untuk melihat (tanpa login). Menulis wajib login admin/super_admin.
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/database.php';
$user = current_user();
$canWrite = $user && can_write($user);

// ---------- PROSES POST (tambah / edit / hapus) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_write($user); // role 'user' ditolak di sini (backend)
    $act = $_POST['action'] ?? '';
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Token keamanan tidak valid. Muat ulang halaman.');
        header('Location: payments.php'); exit;
    }

    if ($act === 'create') {
        $pid = (int)($_POST['participant_id'] ?? 0);
        $amount = (int)($_POST['amount'] ?? 0);
        $date = trim($_POST['payment_date'] ?? '');
        $note = trim($_POST['note'] ?? '');
        // Validasi server-side.
        $ok = $pdo->prepare('SELECT id, name FROM participants WHERE id=?');
        $ok->execute([$pid]);
        $ps = $ok->fetch();
        if (!$ps) flash_set('error', 'Peserta tidak valid.');
        elseif ($amount <= 0 || $amount > 1000000000) flash_set('error', 'Nominal harus antara Rp1 – Rp1.000.000.000.');
        elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) flash_set('error', 'Tanggal tidak valid.');
        else {
            $st = $pdo->prepare('INSERT INTO payments (participant_id, amount, payment_date, note, created_by) VALUES (?,?,?,?,?)');
            $st->execute([$pid, $amount, $date, $note !== '' ? $note : null, (int)$user['id']]);
            $nm = $ps['name'];
            log_activity($pdo, (int)$user['id'], 'tambah_pembayaran', "Menambahkan pembayaran " . rupiah($amount) . " untuk {$nm}");
            flash_set('success', "Pembayaran " . rupiah($amount) . " untuk {$nm} tersimpan.");
        }
        header('Location: payments.php'); exit;
    }

    if ($act === 'update') {
        $id = (int)($_POST['payment_id'] ?? 0);
        $amount = (int)($_POST['amount'] ?? 0);
        $date = trim($_POST['payment_date'] ?? '');
        $note = trim($_POST['note'] ?? '');
        $st = $pdo->prepare('SELECT pay.*, ps.name AS pname FROM payments pay JOIN participants ps ON ps.id=pay.participant_id WHERE pay.id=?');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) flash_set('error', 'Transaksi tidak ditemukan.');
        elseif ($amount <= 0 || $amount > 1000000000) flash_set('error', 'Nominal tidak valid.');
        elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) flash_set('error', 'Tanggal tidak valid.');
        else {
            $pdo->prepare('UPDATE payments SET amount=?, payment_date=?, note=?, updated_by=? WHERE id=?')
                ->execute([$amount, $date, $note !== '' ? $note : null, (int)$user['id'], $id]);
            log_activity($pdo, (int)$user['id'], 'edit_pembayaran', "Mengedit pembayaran {$row['pname']} menjadi " . rupiah($amount) . " (ID #{$id})");
            flash_set('success', 'Pembayaran diperbarui. Total otomatis berubah.');
        }
        header('Location: payments.php'); exit;
    }

    if ($act === 'delete') {
        $id = (int)($_POST['payment_id'] ?? 0);
        $st = $pdo->prepare('SELECT pay.*, ps.name AS pname FROM payments pay JOIN participants ps ON ps.id=pay.participant_id WHERE pay.id=?');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) flash_set('error', 'Transaksi tidak ditemukan.');
        else {
            $pdo->prepare('DELETE FROM payments WHERE id=?')->execute([$id]);
            log_activity($pdo, (int)$user['id'], 'hapus_pembayaran', "Menghapus pembayaran " . rupiah($row['amount']) . " milik {$row['pname']} (ID #{$id})");
            flash_set('success', 'Pembayaran dihapus. Total otomatis diperbarui.');
        }
        header('Location: payments.php'); exit;
    }
}

// ---------- FILTER / SEARCH (server-side via GET) ----------
$q = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'semua'; // semua | sudah | belum

// Ambil semua peserta aktif + total & jumlah transaksi (1 query agregat).
$sql = "SELECT ps.id, ps.name, ps.status,
               COALESCE(SUM(pay.amount),0) AS total,
               COUNT(pay.id) AS ntrans,
               MAX(pay.payment_date) AS last_date
        FROM participants ps
        LEFT JOIN payments pay ON pay.participant_id = ps.id
        WHERE ps.status='aktif'";
$params = [];
if ($q !== '') { $sql .= " AND ps.name LIKE ?"; $params[] = "%{$q}%"; }
$sql .= " GROUP BY ps.id, ps.name, ps.status ORDER BY ps.name ASC";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
if ($filter === 'sudah') $rows = array_values(array_filter($rows, fn($r) => (int)$r['ntrans'] > 0));
if ($filter === 'belum') $rows = array_values(array_filter($rows, fn($r) => (int)$r['ntrans'] === 0));

// Total keseluruhan kas (otomatis dari DB).
$grand = (int)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments')->fetchColumn();

// Daftar peserta untuk dropdown tambah.
$allPeserta = $pdo->query("SELECT id, name FROM participants WHERE status='aktif' ORDER BY name ASC")->fetchAll();

// Riwayat per peserta (untuk modal) — ambil sekaligus agar hemat query.
$histories = [];
if ($rows) {
    $ids = array_column($rows, 'id');
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $h = $pdo->prepare("SELECT pay.*, u.display_name AS admin_name FROM payments pay LEFT JOIN users u ON u.id=pay.created_by WHERE pay.participant_id IN ($ph) ORDER BY pay.payment_date DESC, pay.id DESC");
    $h->execute($ids);
    foreach ($h->fetchAll() as $t) $histories[$t['participant_id']][] = $t;
}

$base = '..';
$pageTitle = 'Kas & Pembayaran';
$pageSub = 'Setiap transaksi tersimpan terpisah — total per orang & total keseluruhan dihitung otomatis.';
$active = 'payments';
$extraJs = ['assets/js/payments.js'];
include __DIR__ . '/../includes/header.php';
?>

<?php if ($canWrite): ?>
<div class="panel">
  <div class="panel-head"><h2>+ Tambah Pembayaran</h2></div>
  <div class="panel-body">
    <form method="post" class="toolbar">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">
      <div class="grow"><label style="margin:0">Pilih Peserta
        <select name="participant_id" required>
          <option value="">— pilih —</option>
          <?php foreach ($allPeserta as $p): ?>
          <option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option>
          <?php endforeach; ?>
        </select></label></div>
      <div><label style="margin:0">Nominal (Rp)
        <input type="number" name="amount" min="1" max="1000000000" step="500" placeholder="5000" required></label></div>
      <div><label style="margin:0">Tanggal
        <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required></label></div>
      <div class="grow"><label style="margin:0">Catatan (opsional)
        <input type="text" name="note" maxlength="255" placeholder="mis. Kas minggu ke-3"></label></div>
      <div><label style="margin:0">&nbsp;<button class="btn btn-primary" type="submit" style="width:100%">Simpan</button></label></div>
    </form>
    <p class="muted small" style="margin:8px 0 0">Admin yang login (<b><?= e($user['display_name']) ?></b>) otomatis tercatat sebagai penginput — tidak bisa dipilih manual.</p>
  </div>
</div>
<?php endif; ?>

<div class="panel">
  <div class="panel-head"><h2>Tabel Kas</h2><span class="spacer"></span>
    <form method="get" class="toolbar" style="flex:1;justify-content:flex-end">
      <div><input type="text" name="q" placeholder="Cari nama, mis. Rayhan" value="<?= e($q) ?>" data-table-filter="kasTable" style="margin:0"></div>
      <div><select name="filter" onchange="this.form.submit()" style="margin:0">
        <option value="semua" <?= $filter==='semua'?'selected':'' ?>>Semua</option>
        <option value="sudah" <?= $filter==='sudah'?'selected':'' ?>>Sudah bayar</option>
        <option value="belum" <?= $filter==='belum'?'selected':'' ?>>Belum bayar</option>
      </select></div>
      <?php if ($q !== '' || $filter !== 'semua'): ?><a class="btn btn-ghost btn-sm" href="payments.php">Reset</a><?php endif; ?>
    </form>
  </div>
  <div class="table-wrap"><table class="grid" id="kasTable">
    <thead><tr><th>No</th><th>Nama</th><th>Riwayat Pembayaran</th><th style="text-align:right">Total</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="4" class="empty">Tidak ada peserta yang cocok.</td></tr><?php endif; ?>
    <?php $no=1; foreach ($rows as $r): $list = $histories[$r['id']] ?? []; ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><strong><?= e($r['name']) ?></strong><br><span class="muted small"><?= (int)$r['ntrans'] ?> transaksi</span></td>
        <td>
          <?php if (!$list): ?><span class="badge neutral">Belum bayar</span>
          <?php else: ?>
            <span class="muted small"><?= tgl($list[0]['payment_date']) ?> <?= rupiah($list[0]['amount']) ?><?= count($list) > 1 ? ' <b>+' . (count($list)-1) . ' lainnya</b>' : '' ?></span><br>
            <button class="btn btn-ghost btn-sm" data-history-of="<?= (int)$r['id'] ?>" data-history-name="<?= e($r['name']) ?>">Lihat Riwayat</button>
          <?php endif; ?>
        </td>
        <td class="num"><strong><?= rupiah($r['total']) ?></strong></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <div class="panel-body" style="border-top:1px solid var(--line)">
    <h2 style="margin:0">Total Keseluruhan Kas: <span style="color:var(--primary)"><?= rupiah($grand) ?></span></h2>
    <p class="muted small" style="margin:4px 0 0">Otomatis dari seluruh transaksi. Bertambah/berkurang saat tambah, edit, atau hapus.</p>
  </div>
</div>

<?php /* Template riwayat per peserta (diklon ke modal oleh app.js) */ ?>
<?php foreach ($rows as $r): $list = $histories[$r['id']] ?? []; if (!$list) continue; ?>
<template id="history-<?= (int)$r['id'] ?>">
  <table class="grid">
    <thead><tr><th>Tanggal</th><th style="text-align:right">Nominal</th><th>Admin</th><?php if ($canWrite): ?><th>Aksi</th><?php endif; ?></tr></thead>
    <tbody>
    <?php $t=0; foreach ($list as $x): $t += (int)$x['amount']; ?>
      <tr>
        <td><?= tgl($x['payment_date']) ?><br><span class="muted small"><?= e($x['note'] ?? '') ?></span></td>
        <td class="num"><?= rupiah($x['amount']) ?></td>
        <td><?= e($x['admin_name'] ?? '-') ?><br><span class="muted small"><?= tgl_jam($x['created_at']) ?></span></td>
        <?php if ($canWrite): ?><td><div class="row-actions">
          <button class="btn btn-ghost btn-sm" data-edit-payment
            data-id="<?= (int)$x['id'] ?>" data-amount="<?= (int)$x['amount'] ?>"
            data-date="<?= e($x['payment_date']) ?>" data-note="<?= e($x['note'] ?? '') ?>"
            data-pname="<?= e($r['name']) ?>">Edit</button>
          <form method="post" class="inline-form" data-confirm="Apakah Anda yakin ingin menghapus pembayaran <?= rupiah($x['amount']) ?> dari <?= e($r['name']) ?>?">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete">
            <input type="hidden" name="payment_id" value="<?= (int)$x['id'] ?>">
            <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
          </form>
        </div></td><?php endif; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <p style="text-align:right"><strong>Total: <?= rupiah($t) ?></strong></p>
</template>
<?php endforeach; ?>

<?php /* Template form edit (diisi payments.js lalu diklon ke modal) */ ?>
<template id="tplEdit">
  <form method="post" id="formEdit">
    <?= csrf_field() ?><input type="hidden" name="action" value="update">
    <input type="hidden" name="payment_id">
    <p class="muted small" id="editInfo"></p>
    <div class="form-grid">
      <label>Nominal (Rp)<input type="number" name="amount" min="1" max="1000000000" required></label>
      <label>Tanggal<input type="date" name="payment_date" required></label>
    </div>
    <label>Catatan<input type="text" name="note" maxlength="255"></label>
    <button class="btn btn-primary btn-block" type="submit">Simpan Perubahan</button>
  </form>
</template>
<?php include __DIR__ . '/../includes/footer.php'; ?>
