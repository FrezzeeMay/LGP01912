<?php
// api/payments/create.php — tambah pembayaran via AJAX.
require_once __DIR__ . '/../_bootstrap.php';
if (!can_write($u)) jres(false, 'Akun User hanya boleh melihat.', null, 403);
$pid = (int)($in['participant_id'] ?? 0);
$amount = (int)($in['amount'] ?? 0);
$date = trim($in['payment_date'] ?? '');
$note = trim($in['note'] ?? '');
$st = $pdo->prepare('SELECT name FROM participants WHERE id=?'); $st->execute([$pid]);
$ps = $st->fetch();
if (!$ps) jres(false, 'Peserta tidak valid.', null, 422);
if ($amount <= 0 || $amount > 1000000000) jres(false, 'Nominal harus 1–1.000.000.000.', null, 422);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) jres(false, 'Tanggal tidak valid (YYYY-MM-DD).', null, 422);
$pdo->prepare('INSERT INTO payments (participant_id, amount, payment_date, note, created_by) VALUES (?,?,?,?,?)')
    ->execute([$pid, $amount, $date, $note !== '' ? $note : null, (int)$u['id']]);
$id = (int)$pdo->lastInsertId();
log_activity($pdo, (int)$u['id'], 'tambah_pembayaran', "Menambahkan pembayaran " . rupiah($amount) . " untuk {$ps['name']} (via API)");
jres(true, 'Pembayaran tersimpan.', ['id' => $id]);
