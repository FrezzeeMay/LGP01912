-- ============================================================
-- KasKita — database/supabase_seed.sql  (PostgreSQL / Supabase)
-- Cara pakai: SQL Editor → New query → tempel isi file → Run.
--
-- Akun (password sudah di-hash dengan password_hash):
--   Alpi        / alpi019@gmail.com    / LGP019      (admin)
--   Naufal      / naufal019@gmail.com  / LGP019      (admin)
--   Super Admin / Rayhan019@gmai.com  / Apalah019   (super_admin)
--   Viewer      / viewer@kaskita.local / Viewer123! (user, lihat saja)
-- ============================================================

TRUNCATE TABLE activity_logs, shirt_participants, shirt_projects,
              payments, participants, users
RESTART IDENTITY CASCADE;

-- ---------- users ----------
INSERT INTO users (username, email, password, display_name, role) VALUES
('alpi',       'alpi019@gmail.com',    '$2y$10$SvZmVqqTpYykdp6OJrqgKO/n20MNr9hQ/yD3wcq3yioQQU4NqoTWS', 'Alpi',        'admin'),
('naufal',     'naufal019@gmail.com',  '$2y$10$SvZmVqqTpYykdp6OJrqgKO/n20MNr9hQ/yD3wcq3yioQQU4NqoTWS', 'Naufal',      'admin'),
('superadmin', 'Rayhan019@gmai.com',   '$2y$10$YokCCtYX0Tio3FpbKw2XKupmePeERIfLq4/wY0dbcsiDU23ClvDa2', 'Super Admin', 'super_admin'),
('viewer',     'viewer@kaskita.local', '$2y$10$v/qwVlui8vVqek8XnqM.KObfB.a1xEbdho6wUbkT.qLKhhGNaWW8W', 'Viewer',      'user');

-- ---------- participants (10 contoh) ----------
INSERT INTO participants (name, status, shirt_size) VALUES
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

-- ---------- payments (dummy agar dashboard hidup) ----------
-- id user: 1=Alpi, 2=Naufal, 3=Super Admin. id peserta sesuai urutan di atas.
INSERT INTO payments (participant_id, amount, payment_date, note, created_by, created_at) VALUES
(1, 5000,  '2026-10-04', 'Kas minggu 1', 1, '2026-10-04 10:30:00+07'),
(1, 5000,  '2026-10-05', 'Kas minggu 2', 2, '2026-10-05 09:15:00+07'),
(2, 10000, '2026-10-04', 'Kas 2 minggu', 1, '2026-10-04 10:35:00+07'),
(4, 5000,  '2026-10-04', NULL,           1, '2026-10-04 10:40:00+07'),
(5, 5000,  '2026-10-03', NULL,           2, '2026-10-03 14:00:00+07'),
(6, 15000, '2026-10-02', 'Kas 3 minggu', 2, '2026-10-02 11:20:00+07'),
(7, 5000,  '2026-10-04', NULL,           1, '2026-10-04 11:00:00+07'),
(8, 10000, '2026-10-01', NULL,           1, '2026-10-01 08:00:00+07'),
(10, 5000, '2026-10-04', NULL,           2, '2026-10-04 12:00:00+07');

-- ---------- shirt_projects ----------
INSERT INTO shirt_projects (name, image, description, price_per_person, participant_count, target_amount, status) VALUES
('Baju Angkatan 2026', NULL, 'Kaos angkatan sablon depan-belakang, bahan cotton combed 30s.', 75000, 10, 750000, 'berjalan');

-- ---------- shirt_participants (anggota project 1) ----------
INSERT INTO shirt_participants (project_id, participant_id, size, amount_paid, status) VALUES
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
INSERT INTO activity_logs (user_id, action, description, created_at) VALUES
(1, 'tambah_pembayaran', 'Menambahkan pembayaran Rp5.000 untuk Rayhan', '2026-10-04 10:30:00+07'),
(2, 'tambah_pembayaran', 'Menambahkan pembayaran Rp5.000 untuk Rayhan', '2026-10-05 09:15:00+07'),
(1, 'tambah_pembayaran', 'Menambahkan pembayaran Rp10.000 untuk Andi',   '2026-10-04 10:35:00+07'),
(3, 'buat_project_baju', 'Membuat project baju "Baju Angkatan 2026" (target Rp750.000)', '2026-10-01 09:00:00+07');
