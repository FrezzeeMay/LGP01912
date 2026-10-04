<?php
// admin/activity.php — Riwayat aktivitas admin (audit log).
// Boleh dibuka admin & super_admin; menampilkan siapa melakukan apa & kapan.
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/database.php';
$user = require_role(['admin', 'super_admin']);

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT l.*, u.display_name, u.username FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id';
$p = [];
if ($q !== '') { $sql .= ' WHERE l.description LIKE ? OR l.action LIKE ? OR u.display_name LIKE ?'; $p = ["%{$q}%","%{$q}%","%{$q}%"]; }
$sql .= ' ORDER BY l.created_at DESC LIMIT 200';
$st = $pdo->prepare($sql); $st->execute($p);
$rows = $st->fetchAll();

$base='..'; $pageTitle='Aktivitas Admin'; $pageSub='Jelas siapa memasukkan / mengubah / menghapus data.'; $active='activity';
include __DIR__ . '/../includes/header.php';
?>
<div class="panel">
  <div class="panel-head"><h2>Riwayat Aktivitas (<?= count($rows) ?> terbaru)</h2><span class="spacer"></span>
    <form method="get"><input type="text" name="q" placeholder="Cari aktivitas / admin…" value="<?= e($q) ?>" style="margin:0"></form></div>
  <div class="table-wrap"><table class="grid">
    <thead><tr><th>Waktu</th><th>Admin</th><th>Aksi</th><th>Aktivitas</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="4" class="empty">Belum ada aktivitas.</td></tr><?php endif; ?>
    <?php foreach ($rows as $l): ?>
      <tr><td style="white-space:nowrap"><?= tgl_jam($l['created_at']) ?></td>
        <td><?= e($l['display_name'] ?? '—') ?><br><span class="muted small"><?= e($l['username'] ?? '') ?></span></td>
        <td><span class="badge info"><?= e($l['action']) ?></span></td>
        <td><?= e($l['description']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
