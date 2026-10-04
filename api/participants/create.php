<?php
// api/participants/create.php
require_once __DIR__ . '/../_bootstrap.php';
if (($u['role'] ?? '') !== 'super_admin') jres(false, 'Hanya Super Admin.', null, 403);
$name = trim($in['name'] ?? '');
$status = $in['status'] ?? 'aktif';
$size = $in['shirt_size'] ?? null;
if ($name === '' || mb_strlen($name) > 100) jres(false, 'Nama 1–100 karakter.', null, 422);
if (!in_array($status, ['aktif','nonaktif'], true)) jres(false, 'Status tidak valid.', null, 422);
$S = ['XS','S','M','L','XL','XXL','XXXL'];
if ($size !== null && $size !== '' && !in_array($size, $S, true)) jres(false, 'Ukuran tidak valid.', null, 422);
$pdo->prepare('INSERT INTO participants (name,status,shirt_size) VALUES (?,?,?)')->execute([$name,$status,($size==='' ? null : $size)]);
$id = (int)$pdo->lastInsertId();
log_activity($pdo, (int)$u['id'], 'tambah_peserta', "Menambahkan peserta {$name} (via API)");
jres(true, 'Peserta ditambahkan.', ['id'=>$id]);
