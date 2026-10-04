<?php
// admin/dashboard.php — ringkasan statistik + aktivitas terbaru.
// PUBLIK: bisa dibuka tanpa login (hanya melihat). Tombol tulis hanya untuk admin.
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/database.php';
$user = current_user();
$canWrite = $user && can_write($user);
$base = '..';
$pageTitle = 'Dashboard';
$pageSub = 'Ringkasan kas kelas & baju gabungan — semua angka dihitung otomatis dari database.';
$active = 'dashboard';

// --- Statistik kas ---
$totalKas = (int)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments')->fetchColumn();
$jmlPeserta = (int)$pdo->query("SELECT COUNT(*) FROM participants WHERE status='aktif'")->fetchColumn();
$sudahBayar = (int)$pdo->query('SELECT COUNT(DISTINCT participant_id) FROM payments')->fetchColumn();
// Sudah bayar hanya yang masih aktif:
$sudahBayar = (int)$pdo->query("SELECT COUNT(DISTINCT p.participant_id) FROM payments p JOIN participants ps ON ps.id=p.participant_id WHERE ps.status='aktif'")->fetchColumn();
$belumBayar = max(0, $jmlPeserta - $sudahBayar);

// --- Statistik baju ---
$danaBaju = (int)$pdo->query('SELECT COALESCE(SUM(amount_paid),0) FROM shirt_participants')->fetchColumn();
$targetBaju = (int)$pdo->query("SELECT COALESCE(SUM(target_amount),0) FROM shirt_projects WHERE status='berjalan'")->fetchColumn();

$recent = $pdo->query(
    'SELECT pay.*, ps.name AS pname FROM payments pay JOIN participants ps ON ps.id=pay.participant_id ORDER BY pay.created_at DESC LIMIT 8'
)->fetchAll();
$logs = $pdo->query(
    'SELECT l.*, u.display_name FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.created_at DESC LIMIT 8'
)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<section class="stats">
  <div class="stat"><small>Total Kas</small><strong><?= rupiah($totalKas) ?></strong><span>dari seluruh transaksi</span></div>
  <div class="stat"><small>Jumlah Peserta</small><strong><?= (int)$jmlPeserta ?> Orang</strong><span>status aktif</span></div>
  <div class="stat"><small>Sudah Bayar</small><strong><?= (int)$sudahBayar ?> Orang</strong><span>pernah membayar</span></div>
  <div class="stat"><small>Belum Bayar</small><strong><?= (int)$belumBayar ?> Orang</strong><span>belum ada transaksi</span></div>
  <div class="stat"><small>Dana Baju</small><strong><?= rupiah($danaBaju) ?></strong><span>terkumpul semua project</span></div>
  <div class="stat"><small>Target Baju</small><strong><?= rupiah($targetBaju) ?></strong><span>project berjalan</span></div>
</section>

<div class="panel">
  <div class="panel-head"><h2>Pembayaran Terakhir</h2><span class="spacer"></span>
    <?php if ($canWrite): ?><a class="btn btn-primary btn-sm" href="payments.php">+ Tambah Pembayaran</a><?php endif; ?>
    <a class="btn btn-ghost btn-sm" href="payments.php">Lihat Semua</a></div>
  <div class="table-wrap"><table class="grid">
    <thead><tr><th>Nama</th><th>Nominal</th><th>Tanggal</th><th>Dimasukkan Oleh</th></tr></thead>
    <tbody>
    <?php if (!$recent): ?><tr><td colspan="4" class="empty">Belum ada pembayaran.</td></tr><?php endif; ?>
    <?php foreach ($recent as $r): ?>
      <tr><td><?= e($r['pname']) ?></td><td class="num"><?= rupiah($r['amount']) ?></td>
      <td><?= tgl($r['payment_date']) ?></td><td><?= e(admin_name($pdo, $r['created_by'])) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<?php if ($user && can_write($user)): ?>
<div class="panel">
  <div class="panel-head"><h2>Aktivitas Admin Terakhir</h2><span class="spacer"></span>
    <a class="btn btn-ghost btn-sm" href="activity.php">Lihat Semua</a></div>
  <div class="table-wrap"><table class="grid">
    <thead><tr><th>Waktu</th><th>Admin</th><th>Aktivitas</th></tr></thead>
    <tbody>
    <?php if (!$logs): ?><tr><td colspan="3" class="empty">Belum ada aktivitas.</td></tr><?php endif; ?>
    <?php foreach ($logs as $l): ?>
      <tr><td style="white-space:nowrap"><?= tgl_jam($l['created_at']) ?></td>
      <td><?= e($l['display_name'] ?? '-') ?></td><td><?= e($l['description']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
