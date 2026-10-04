-- ============================================================
-- KasKita — database/supabase_migration_01.sql
-- Migrasi untuk database Supabase yang SUDAH dibuat sebelum fitur
-- "wajib ganti password" ada. Cara pakai: SQL Editor → New query →
-- tempel isi file ini → Run. Aman dijalankan ulang.
-- (Database baru yang memakai supabase_schema.sql terbaru TIDAK perlu ini.)
-- ============================================================

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS must_change_password BOOLEAN NOT NULL DEFAULT TRUE;

-- Paksa semua akun lama ganti password (password lama sudah pernah tertulis
-- di file/repo sehingga dianggap bocor).
UPDATE users SET must_change_password = TRUE;
