<?php
// api/participants/delete.php
require_once __DIR__ . '/../_bootstrap.php';
if (($u['role'] ?? '') !== 'super_admin') jres(false, 'Hanya Super Admin.', null, 403);
$id = (int)($in['id'] ?? 0);
$pdo->prepare('DELETE FROM participants WHERE id=?')->execute([$id]);
log_activity($pdo, (int)$u['id'], 'hapus_peserta', "Menghapus peserta ID #{$id} (via API)");
jres(true, 'Peserta dihapus.');
