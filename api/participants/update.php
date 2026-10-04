<?php
// api/participants/update.php
require_once __DIR__ . '/../_bootstrap.php';
if (($u['role'] ?? '') !== 'super_admin') jres(false, 'Hanya Super Admin.', null, 403);
$id = (int)($in['id'] ?? 0);
$name = trim($in['name'] ?? '');
$status = $in['status'] ?? 'aktif';
$size = $in['shirt_size'] ?? null;
if ($name === '' || mb_strlen($name) > 100) jres(false, 'Nama 1–100 karakter.', null, 422);
if (!in_array($status, ['aktif','nonaktif'], true)) jres(false, 'Status tidak valid.', null, 422);
$S = ['XS','S','M','L','XL','XXL','XXXL'];
if ($size !== null && $size !== '' && !in_array($size, $S, true)) jres(false, 'Ukuran tidak valid.', null, 422);
$pdo->prepare('UPDATE participants SET name=?,status=?,shirt_size=? WHERE id=?')->execute([$name,$status,($size==='' ? null : $size),$id]);
log_activity($pdo, (int)$u['id'], 'edit_peserta', "Mengedit peserta {$name} (ID #{$id}, via API)");
jres(true, 'Peserta diperbarui.');
