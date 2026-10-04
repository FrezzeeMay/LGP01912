<?php
// index.php — pintu masuk: arahkan ke dashboard jika login, ke login jika belum.
require_once __DIR__ . '/auth/auth.php';
if (current_user()) {
    header('Location: admin/dashboard.php');
} else {
    header('Location: login.php');
}
exit;
