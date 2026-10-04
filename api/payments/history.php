<?php
// api/payments/history.php — GET ?participant_id= : riwayat transaksi satu peserta (JSON).
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../config/database.php';
// PUBLIK (read-only): riwayat kas boleh dilihat tanpa login.
// (Endpoint tulis create/update/delete tetap wajib login + can_write.)
$u = current_user();
$pid = (int)($_GET['participant_id'] ?? 0);
$st = $pdo->prepare('SELECT pay.id, pay.amount, pay.payment_date, pay.note, pay.created_at, u.display_name AS admin_name FROM payments pay LEFT JOIN users u ON u.id=pay.created_by WHERE pay.participant_id=? ORDER BY pay.payment_date DESC, pay.id DESC');
$st->execute([$pid]);
$rows = $st->fetchAll();
$total = array_sum(array_column($rows, 'amount'));
echo json_encode(['ok'=>true, 'data'=>['items'=>$rows, 'total'=>$total]], JSON_UNESCAPED_UNICODE);
