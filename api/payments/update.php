<?php
// api/payments/update.php — edit nominal/tanggal/catatan via AJAX.
require_once __DIR__ . '/../_bootstrap.php';
if (!can_write($u)) jres(false, 'Akun User hanya boleh melihat.', null, 403);
$id = (int)($in['payment_id'] ?? $in['id'] ?? 0);
$amount = (int)($in['amount'] ?? 0);
$date = trim($in['payment_date'] ?? '');
$note = trim($in['note'] ?? '');
$st = $pdo->prepare('SELECT pay.*, ps.name AS pname FROM payments pay JOIN participants ps ON ps.id=pay.participant_id WHERE pay.id=?');
$st->execute([$id]); $row = $st->fetch();
if (!$row) jres(false, 'Transaksi tidak ditemukan.', null, 404);
if ($amount <= 0 || $amount > 1000000000) jres(false, 'Nominal tidak valid.', null, 422);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) jres(false, 'Tanggal tidak valid.', null, 422);
$pdo->prepare('UPDATE payments SET amount=?, payment_date=?, note=?, updated_by=? WHERE id=?')
    ->execute([$amount, $date, $note !== '' ? $note : null, (int)$u['id'], $id]);
log_activity($pdo, (int)$u['id'], 'edit_pembayaran', "Mengedit pembayaran {$row['pname']} menjadi " . rupiah($amount) . " (ID #{$id}, via API)");
jres(true, 'Pembayaran diperbarui.');
