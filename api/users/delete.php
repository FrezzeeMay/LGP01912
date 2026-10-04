<?php
// api/users/delete.php — super_admin saja.
require_once __DIR__ . '/../_bootstrap.php';
if (($u['role'] ?? '') !== 'super_admin') jres(false, 'Hanya Super Admin.', null, 403);
$id=(int)($in['id']??0);
if($id===(int)$u['id']) jres(false,'Tidak bisa menghapus akun sendiri.',null,422);
$pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
log_activity($pdo,(int)$u['id'],'hapus_admin',"Menghapus akun ID #{$id} (via API)");
jres(true,'Akun dihapus.');
