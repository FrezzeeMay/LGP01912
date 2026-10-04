<?php
// index.php — pintu masuk: dashboard PUBLIK (bisa dilihat tanpa login).
// Yang butuh login hanya aksi tulis (otomatis dilempar ke login.php).
header('Location: admin/dashboard.php');
exit;
