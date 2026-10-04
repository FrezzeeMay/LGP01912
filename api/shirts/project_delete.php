<?php
// api/shirts/project_delete.php — hapus project (super_admin saja).
require_once __DIR__ . '/../_bootstrap.php';
if (($u['role'] ?? '') !== 'super_admin') { http_response_code(403); echo json_encode(['ok'=>false,'message'=>'Hanya Super Admin.']); exit; }
$id = (int)($in['project_id'] ?? $in['id'] ?? 0);
$nm = $pdo->prepare('SELECT image FROM shirt_projects WHERE id=?'); $nm->execute([$id]); $r = $nm->fetch();
$pdo->prepare('DELETE FROM shirt_projects WHERE id=?')->execute([$id]);
if (!empty($r['image']) && file_exists(__DIR__ . '/../../' . $r['image'])) @unlink(__DIR__ . '/../../' . $r['image']);
log_activity($pdo, (int)$u['id'], 'hapus_project_baju', "Menghapus project #{$id} (via API)");
echo json_encode(['ok'=>true,'message'=>'Project dihapus.'], JSON_UNESCAPED_UNICODE);
