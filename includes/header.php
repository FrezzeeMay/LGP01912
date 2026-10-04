<?php
// includes/header.php — Layout bersama (sidebar + topbar glass, konten flat).
// Variabel yang diharapkan: $pageTitle (string), $active (string), $user (array), $base (string, default '').
// Contoh: $base = '..' saat dipanggil dari admin/*.php; $base = '' dari root.
require_once __DIR__ . '/../auth/auth.php';
$base = $base ?? '';
$user = $user ?? current_user();
$flashes = flash_get();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'KasKita') ?> — KasKita</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e($base) ?>assets/css/style.css">
<link rel="stylesheet" href="<?= e($base) ?>assets/css/responsive.css">
</head>
<body>
<div class="app">
  <button class="nav-toggle" id="navToggle" aria-label="Buka/tutup navigasi">
    <span></span><span></span><span></span>
  </button>
  <div class="scrim" id="scrim"></div>

  <aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= e($base) ?>admin/dashboard.php">
      <span class="brand-mark">K</span>
      <span class="brand-text"><strong>KasKita</strong><small>Sistem Kas &amp; Pembuatan Baju</small></span>
    </a>
    <?php if ($user): ?>
    <nav class="side-nav">
      <a href="<?= e($base) ?>admin/dashboard.php" class="<?= ($active ?? '') === 'dashboard' ? 'on' : '' ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
        Dashboard</a>
      <a href="<?= e($base) ?>admin/payments.php" class="<?= ($active ?? '') === 'payments' ? 'on' : '' ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>
        Kas &amp; Pembayaran</a>
      <a href="<?= e($base) ?>admin/participants.php" class="<?= ($active ?? '') === 'participants' ? 'on' : '' ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Peserta</a>
      <a href="<?= e($base) ?>admin/shirts.php" class="<?= ($active ?? '') === 'shirts' ? 'on' : '' ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/></svg>
        Baju Gabungan</a>
      <?php if (($user['role'] ?? '') !== 'user'): ?>
      <a href="<?= e($base) ?>admin/activity.php" class="<?= ($active ?? '') === 'activity' ? 'on' : '' ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
        Aktivitas</a>
      <?php endif; ?>
      <?php if (($user['role'] ?? '') === 'super_admin'): ?>
      <a href="<?= e($base) ?>admin/users.php" class="<?= ($active ?? '') === 'users' ? 'on' : '' ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        Kelola Admin</a>
      <?php endif; ?>
    </nav>
    <div class="side-user">
      <div class="avatar"><?= e(mb_strtoupper(mb_substr($user['display_name'] ?? 'A', 0, 1))) ?></div>
      <div class="who"><strong><?= e($user['display_name'] ?? '-') ?></strong>
        <small><?= e(role_label($user['role'] ?? '')) ?> · <?= e($user['username'] ?? '') ?></small></div>
      <a class="btn btn-ghost btn-sm" href="<?= e($base) ?>logout.php">Keluar</a>
    </div>
    <?php else: ?>
    <nav class="side-nav">
      <a href="<?= e($base) ?>admin/dashboard.php" class="<?= ($active ?? '') === 'dashboard' ? 'on' : '' ?>">Dashboard</a>
      <a href="<?= e($base) ?>admin/payments.php" class="<?= ($active ?? '') === 'payments' ? 'on' : '' ?>">Kas &amp; Pembayaran</a>
      <a href="<?= e($base) ?>admin/participants.php" class="<?= ($active ?? '') === 'participants' ? 'on' : '' ?>">Peserta</a>
      <a href="<?= e($base) ?>admin/shirts.php" class="<?= ($active ?? '') === 'shirts' ? 'on' : '' ?>">Baju Gabungan</a>
    </nav>
    <div class="side-user">
      <div class="who"><strong>Mode Lihat</strong><small>Tamu — tanpa login</small></div>
      <a class="btn btn-primary btn-sm" href="<?= e($base) ?>login.php">Masuk</a>
    </div>
    <?php endif; ?>
  </aside>

  <div class="main">
    <header class="topbar">
      <div class="topbar-title">
        <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
        <?php if (!empty($pageSub)): ?><p><?= e($pageSub) ?></p><?php endif; ?>
      </div>
      <?php if ($user): ?>
      <div class="topbar-meta">
        <span class="role-badge <?= e($user['role'] ?? 'admin') ?>"><?= e(role_label($user['role'] ?? 'admin')) ?></span>
        <span class="clock" id="clock"></span>
      </div>
      <?php else: ?>
      <div class="topbar-meta">
        <a class="btn btn-primary btn-sm" href="<?= e($base) ?>login.php">Masuk Admin</a>
      </div>
      <?php endif; ?>
    </header>

    <main class="content">
      <?php foreach ($flashes as $f): ?>
        <div class="alert <?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
      <?php endforeach; ?>
