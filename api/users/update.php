<?php
// api/users/update.php — super_admin saja.
require_once __DIR__ . '/../_bootstrap.php';
if (($u['role'] ?? '') !== 'super_admin') jres(false, 'Hanya Super Admin.', null, 403);
$id=(int)($in['id']??0);
$username=trim($in['username']??''); $email=trim($in['email']??''); $dname=trim($in['display_name']??'');
$role=$in['role']??'admin'; $pass=$in['password']??'';
if(!preg_match('/^[A-Za-z0-9_]{3,50}$/',$username)||!filter_var($email,FILTER_VALIDATE_EMAIL)||$dname===''||!in_array($role,['admin','super_admin','user'],true)) jres(false,'Input tidak valid.',null,422);
try {
  if($pass!==''){ if(strlen($pass)<8) jres(false,'Password minimal 8 karakter.',null,422);
    $pdo->prepare('UPDATE users SET username=?,email=?,password=?,display_name=?,role=? WHERE id=?')->execute([$username,$email,password_hash($pass,PASSWORD_DEFAULT),$dname,$role,$id]);
  } else $pdo->prepare('UPDATE users SET username=?,email=?,display_name=?,role=? WHERE id=?')->execute([$username,$email,$dname,$role,$id]);
  log_activity($pdo,(int)$u['id'],'edit_admin',"Mengedit akun {$username} (via API)");
  jres(true,'Akun diperbarui.');
} catch (PDOException $ex) { jres(false,'Username/email sudah dipakai.',null,409); }
