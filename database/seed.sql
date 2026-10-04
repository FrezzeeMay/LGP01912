-- ============================================================
-- KasKita — database/seed.sql
-- Akun (password sudah di-hash dengan password_hash):
--   admin_a      / AdminA123!      (role: admin)
--   admin_b      / AdminB123!      (role: admin)
--   superadmin   / SuperAdmin123!  (role: super_admin)
-- ============================================================
USE `kaskita_db`;

-- Bersihkan dulu agar seed bisa dijalankan ulang (urutan per FK).
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `activity_logs`;
TRUNCATE TABLE `shirt_participants`;
TRUNCATE TABLE `shirt_projects`;
TRUNCATE TABLE `payments`;
TRUNCATE TABLE `participants`;
TRUNCATE TABLE `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------- users ----------
INSERT INTO `users` (`username`, `email`, `password`, `display_name`, `role`) VALUES
('admin_a',    'admin_a@kaskita.local',    '$2y$10$rLcSySwVMYDC8en9dNkS7OgHhMwcaneIk6G0ut3WiwRmheHN9yu/.', 'Admin A',    'admin'),
('admin_b',    'admin_b@kaskita.local',    '$2y$10$1sEYwEwrizyt65Ed4MdOyeAhMgTiqgUaA1VvmGlmRSTVZ3H/z4rCG', 'Admin B',    'admin'),
('superadmin', 'superadmin@kaskita.local', '$2y$10$wf0.7C0I5.EevEKSCDx0eu7dAgqknwhCo1wlPDOvBvZGVN4l3hUe6', 'Super Admin','super_admin');

-- ---------- participants (10 contoh) ----------
INSERT INTO `participants` (`name`, `status`, `shirt_size`) VALUES
('Rayhan', 'aktif', 'L'),
('Andi',   'aktif', 'M'),
('Budi',   'aktif', 'XL'),
('Dimas',  'aktif', 'L'),
('Fajar',  'aktif', 'M'),
('Rizky',  'aktif', 'XL'),
('Reza',   'aktif', 'S'),
('Aldi',   'aktif', 'L'),
('Bagas',  'aktif', 'XXL'),
('Farhan', 'aktif', 'M');

-- ---------- payments (transaksi dummy agar dashboard hidup) ----------
-- id user: 1=Admin A, 2=Admin B, 3=Super Admin. id peserta sesuai urutan di atas.
INSERT INTO `payments` (`participant_id`, `amount`, `payment_date`, `note`, `created_by`, `updated_by`, `created_at`) VALUES
(1, 5000,  '2026-10-04', 'Kas minggu 1', 1, NULL, '2026-10-04 10:30:00'),
(1, 5000,  '2026-10-05', 'Kas minggu 2', 2, NULL, '2026-10-05 09:15:00'),
(2, 10000, '2026-10-04', 'Kas 2 minggu', 1, NULL, '2026-10-04 10:35:00'),
(4, 5000,  '2026-10-04', NULL,           1, NULL, '2026-10-04 10:40:00'),
(5, 5000,  '2026-10-03', NULL,           2, NULL, '2026-10-03 14:00:00'),
(6, 15000, '2026-10-02', 'Kas 3 minggu', 2, NULL, '2026-10-02 11:20:00'),
(7, 5000,  '2026-10-04', NULL,           1, NULL, '2026-10-04 11:00:00'),
(8, 10000, '2026-10-01', NULL,           1, NULL, '2026-10-01 08:00:00'),
(10, 5000, '2026-10-04', NULL,           2, NULL, '2026-10-04 12:00:00');

-- ---------- shirt_projects ----------
INSERT INTO `shirt_projects` (`name`, `image`, `description`, `price_per_person`, `participant_count`, `target_amount`, `status`) VALUES
('Baju Angkatan 2026', NULL, 'Kaos angkatan sablon depan-belakang, bahan cotton combed 30s.', 75000, 10, 750000, 'berjalan');

-- ---------- shirt_participants (anggota project 1) ----------
INSERT INTO `shirt_participants` (`project_id`, `participant_id`, `size`, `amount_paid`, `status`) VALUES
(1, 1,  'L',   75000, 'lunas'),
(1, 2,  'M',   75000, 'lunas'),
(1, 3,  'XL',  50000, 'sebagian'),
(1, 4,  'L',   75000, 'lunas'),
(1, 5,  'M',       0, 'belum_bayar'),
(1, 6,  'XL', 100000, 'lunas'),
(1, 7,  'S',   25000, 'sebagian'),
(1, 8,  'L',   75000, 'lunas'),
(1, 9,  'XXL',     0, 'belum_bayar'),
(1, 10, 'M',   75000, 'lunas');

-- ---------- activity_logs ----------
INSERT INTO `activity_logs` (`user_id`, `action`, `description`, `created_at`) VALUES
(1, 'tambah_pembayaran', 'Menambahkan pembayaran Rp5.000 untuk Rayhan', '2026-10-04 10:30:00'),
(2, 'tambah_pembayaran', 'Menambahkan pembayaran Rp5.000 untuk Rayhan', '2026-10-05 09:15:00'),
(1, 'tambah_pembayaran', 'Menambahkan pembayaran Rp10.000 untuk Andi',   '2026-10-04 10:35:00'),
(3, 'buat_project_baju', 'Membuat project baju "Baju Angkatan 2026" (target Rp750.000)', '2026-10-01 09:00:00');
