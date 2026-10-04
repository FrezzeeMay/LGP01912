<?php
// api/shirts/project_save.php — buat/ubah project via AJAX multipart (mode=create|update, super_admin saja).
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../config/database.php';
$u = current_user();
if (!$u || ($u['role'] ?? '') !== 'super_admin') { http_response_code(403); echo json_encode(['ok'=>false,'message'=>'Hanya Super Admin.']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'message'=>'Harus POST.']); exit; }
if (!verify_csrf($_POST['csrf_token'] ?? null)) { http_response_code(419); echo json_encode(['ok'=>false,'message'=>'CSRF tidak valid.']); exit; }
$mode = $_POST['mode'] ?? 'create';
$name = trim($_POST['name'] ?? ''); $price = (int)($_POST['price_per_person'] ?? 0);
$count = (int)($_POST['participant_count'] ?? 0); $status = $_POST['status'] ?? 'berjalan';
if ($name === '' || $price < 0 || $count < 0 || !in_array($status, ['berjalan','selesai','batal'], true)) {
    http_response_code(422); echo json_encode(['ok'=>false,'message'=>'Input tidak valid.']); exit;
}
$target = $price * $count;
$imgPath = null;
if (!empty($_FILES['image']['name'] ?? '')) {
    $f = $_FILES['image'];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'], true) || ($f['size'] ?? 0) > 2*1024*1024) {
        http_response_code(422); echo json_encode(['ok'=>false,'message'=>'Foto harus JPG/PNG/WebP ≤ 2MB.']); exit;
    }
    $dir = __DIR__ . '/../../uploads/shirts/'; if (!is_dir($dir)) mkdir($dir, 0755, true);
    $imgPath = 'uploads/shirts/baju_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    move_uploaded_file($f['tmp_name'], __DIR__ . '/../../' . $imgPath);
}
$desc = trim($_POST['description'] ?? '');
if ($mode === 'create') {
    $pdo->prepare('INSERT INTO shirt_projects (name,image,description,price_per_person,participant_count,target_amount,status) VALUES (?,?,?,?,?,?,?)')
        ->execute([$name, $imgPath, $desc !== '' ? $desc : null, $price, $count, $target, $status]);
    log_activity($pdo, (int)$u['id'], 'buat_project_baju', "Membuat project \"{$name}\" (via API)");
    echo json_encode(['ok'=>true,'message'=>'Project dibuat.','data'=>['id'=>(int)$pdo->lastInsertId(),'target'=>$target]], JSON_UNESCAPED_UNICODE);
} else {
    $id = (int)($_POST['project_id'] ?? 0);
    if ($imgPath) $pdo->prepare('UPDATE shirt_projects SET name=?,image=?,description=?,price_per_person=?,participant_count=?,target_amount=?,status=? WHERE id=?')->execute([$name,$imgPath,$desc?:null,$price,$count,$target,$status,$id]);
    else $pdo->prepare('UPDATE shirt_projects SET name=?,description=?,price_per_person=?,participant_count=?,target_amount=?,status=? WHERE id=?')->execute([$name,$desc?:null,$price,$count,$target,$status,$id]);
    log_activity($pdo, (int)$u['id'], 'edit_project_baju', "Mengedit project \"{$name}\" (via API)");
    echo json_encode(['ok'=>true,'message'=>'Project diperbarui.','data'=>['target'=>$target]], JSON_UNESCAPED_UNICODE);
}
