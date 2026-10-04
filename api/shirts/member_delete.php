<?php
// api/shirts/member_delete.php
require_once __DIR__ . '/../_bootstrap.php';
if (!can_write($u)) { http_response_code(403); echo json_encode(['ok'=>false,'message'=>'Akun User hanya boleh melihat.']); exit; }
$mid = (int)($in['member_id'] ?? $in['id'] ?? 0);
$pdo->prepare('DELETE FROM shirt_participants WHERE id=?')->execute([$mid]);
log_activity($pdo, (int)$u['id'], 'hapus_anggota_baju', "Menghapus anggota baju #{$mid} (via API)");
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok'=>true,'message'=>'Anggota dihapus.'], JSON_UNESCAPED_UNICODE);
