<?php
// api/users/create.php — super_admin saja.
require_once __DIR__ . '/../_bootstrap.php';
if (($u['role'] ?? '') !== 'super_admin') jres(false, 'Hanya Super Admin.', null, 403);
$username=trim($in['username']??''); $email=trim($in['email']??''); $dname=trim($in['display_name']??'');
$role=$in['role']??'admin'; $pass=$in['password']??'';
if(!preg_match('/^[A-Za-z0-9_]{3,50}$/',$username)||!filter_var($email,FILTER_VALIDATE_EMAIL)||$dname===''||!in_array($role,['admin','super_admin','user'],true)) jres(false,'Input tidak valid.',null,422);
if(strlen($pass)<8) jres(false,'Password minimal 8 karakter.',null,422);
try {
  $pdo->prepare('INSERT INTO users (username,email,password,display_name,role,must_change_password) VALUES (?,?,?,?,?,TRUE)')->execute([$username,$email,password_hash($pass,PASSWORD_DEFAULT),$dname,$role]);
  log_activity($pdo,(int)$u['id'],'tambah_admin',"Menambahkan akun {$username} (via API)");
  jres(true,'Akun dibuat.',['id'=>(int)$pdo->lastInsertId()]);
} catch (PDOException $ex) { jres(false,'Username/email sudah dipakai.',null,409); }
