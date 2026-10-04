<?php
// api/shirts/member_save.php — tambah/ubah anggota + setoran (action=add|update|pay).
require_once __DIR__ . '/../_bootstrap.php';
if (!can_write($u)) jres(false, 'Akun User hanya boleh melihat.', null, 403);
$SIZES = ['XS','S','M','L','XL','XXL','XXXL'];
$mode = $in['mode'] ?? 'add';
if ($mode === 'add') {
    $pid=(int)($in['project_id']??0); $part=(int)($in['participant_id']??0);
    $size=$in['size']??'L'; $paid=(int)($in['amount_paid']??0);
    if(!in_array($size,$SIZES,true)) jres(false,'Ukuran tidak valid.',null,422);
    $pr=$pdo->prepare('SELECT price_per_person FROM shirt_projects WHERE id=?'); $pr->execute([$pid]); $proj=$pr->fetch();
    if(!$proj) jres(false,'Project tidak ditemukan.',null,404);
    $stt=shirt_pay_status($paid,(int)$proj['price_per_person']);
    $pdo->prepare('INSERT INTO shirt_participants (project_id,participant_id,size,amount_paid,status) VALUES (?,?,?,?,?) ON CONFLICT (project_id, participant_id) DO UPDATE SET size=EXCLUDED.size,amount_paid=EXCLUDED.amount_paid,status=EXCLUDED.status')->execute([$pid,$part,$size,$paid,$stt]);
    log_activity($pdo,(int)$u['id'],'tambah_anggota_baju',"Menambah anggota project #{$pid} (via API)");
    jres(true,'Anggota disimpan.');
}
if ($mode === 'update') {
    $mid=(int)($in['member_id']??0); $size=$in['size']??'L'; $paid=(int)($in['amount_paid']??0);
    if(!in_array($size,$SIZES,true)) jres(false,'Ukuran tidak valid.',null,422);
    $m=$pdo->prepare('SELECT sp.*, p.price_per_person FROM shirt_participants sp JOIN shirt_projects p ON p.id=sp.project_id WHERE sp.id=?');
    $m->execute([$mid]); $row=$m->fetch();
    if(!$row) jres(false,'Anggota tidak ditemukan.',null,404);
    $pdo->prepare('UPDATE shirt_participants SET size=?,amount_paid=?,status=? WHERE id=?')->execute([$size,$paid,shirt_pay_status($paid,(int)$row['price_per_person']),$mid]);
    log_activity($pdo,(int)$u['id'],'edit_anggota_baju',"Mengedit anggota #{$mid} (via API)");
    jres(true,'Anggota diperbarui.');
}
if ($mode === 'pay') {
    $mid=(int)($in['member_id']??0); $add=(int)($in['add_amount']??0);
    if($add<=0) jres(false,'Nominal tidak valid.',null,422);
    $m=$pdo->prepare('SELECT sp.*, p.price_per_person FROM shirt_participants sp JOIN shirt_projects p ON p.id=sp.project_id WHERE sp.id=?');
    $m->execute([$mid]); $row=$m->fetch();
    if(!$row) jres(false,'Anggota tidak ditemukan.',null,404);
    $new=(int)$row['amount_paid']+$add;
    $pdo->prepare('UPDATE shirt_participants SET amount_paid=?,status=? WHERE id=?')->execute([$new,shirt_pay_status($new,(int)$row['price_per_person']),$mid]);
    log_activity($pdo,(int)$u['id'],'bayar_baju',"Setoran baju ".rupiah($add)." (via API)");
    jres(true,'Setoran tercatat.',['total'=>$new]);
}
jres(false,'Mode tidak dikenal.',null,422);
