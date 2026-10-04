<?php
// api/payments/delete.php — hapus transaksi via AJAX.
require_once __DIR__ . '/../_bootstrap.php';
if (!can_write($u)) jres(false, 'Akun User hanya boleh melihat.', null, 403);
$id = (int)($in['payment_id'] ?? $in['id'] ?? 0);
$st = $pdo->prepare('SELECT pay.*, ps.name AS pname FROM payments pay JOIN participants ps ON ps.id=pay.participant_id WHERE pay.id=?');
$st->execute([$id]); $row = $st->fetch();
if (!$row) jres(false, 'Transaksi tidak ditemukan.', null, 404);
$pdo->prepare('DELETE FROM payments WHERE id=?')->execute([$id]);
log_activity($pdo, (int)$u['id'], 'hapus_pembayaran', "Menghapus pembayaran " . rupiah($row['amount']) . " milik {$row['pname']} (ID #{$id}, via API)");
jres(true, 'Pembayaran dihapus.');
