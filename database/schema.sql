-- ============================================================
-- KasKita — database/schema.sql
-- Cara import (pilih salah satu):
--  1) phpMyAdmin (http://localhost/phpmyadmin) > buat DB "kaskita_db"
--     > tab Import > pilih schema.sql > Go, lalu seed.sql > Go
--  2) MySQL CLI:  mysql -u root < database/schema.sql
-- ============================================================
CREATE DATABASE IF NOT EXISTS `kaskita_db`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kaskita_db`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------- users (akun admin) ----------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL COMMENT 'hash via password_hash(), bukan plaintext',
  `display_name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin','super_admin','user') NOT NULL DEFAULT 'admin',
  `must_change_password` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = wajib ganti password saat login berikut',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- participants (peserta kas) ----------
CREATE TABLE IF NOT EXISTS `participants` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `status` ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `shirt_size` ENUM('XS','S','M','L','XL','XXL','XXXL') NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_participants_name` (`name`),
  KEY `idx_participants_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- payments (setiap transaksi kas) ----------
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `participant_id` INT UNSIGNED NOT NULL,
  `amount` INT NOT NULL COMMENT 'nominal rupiah, > 0',
  `payment_date` DATE NOT NULL,
  `note` TEXT NULL,
  `created_by` INT UNSIGNED NULL COMMENT 'id admin yang menginput',
  `updated_by` INT UNSIGNED NULL COMMENT 'id admin yang terakhir mengubah',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_participant` (`participant_id`),
  KEY `idx_payments_date` (`payment_date`),
  CONSTRAINT `fk_payments_participant` FOREIGN KEY (`participant_id`)
    REFERENCES `participants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_creator` FOREIGN KEY (`created_by`)
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_updater` FOREIGN KEY (`updated_by`)
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_payments_amount` CHECK (`amount` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- shirt_projects (project baju gabungan) ----------
CREATE TABLE IF NOT EXISTS `shirt_projects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `image` VARCHAR(255) NULL COMMENT 'path file di uploads/shirts/',
  `description` TEXT NULL,
  `price_per_person` INT NOT NULL,
  `participant_count` INT NOT NULL DEFAULT 0 COMMENT 'jumlah peserta target (untuk hitung target)',
  `target_amount` INT NOT NULL DEFAULT 0 COMMENT 'otomatis = price * participant_count',
  `status` ENUM('berjalan','selesai','batal') NOT NULL DEFAULT 'berjalan',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_shirt_price` CHECK (`price_per_person` >= 0),
  CONSTRAINT `chk_shirt_count` CHECK (`participant_count` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- shirt_participants (anggota per project baju) ----------
CREATE TABLE IF NOT EXISTS `shirt_participants` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `participant_id` INT UNSIGNED NOT NULL,
  `size` ENUM('XS','S','M','L','XL','XXL','XXXL') NOT NULL DEFAULT 'L',
  `amount_paid` INT NOT NULL DEFAULT 0,
  `status` ENUM('belum_bayar','sebagian','lunas') NOT NULL DEFAULT 'belum_bayar',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_shirt_member` (`project_id`, `participant_id`),
  KEY `idx_shirtmem_project` (`project_id`),
  CONSTRAINT `fk_shirtmem_project` FOREIGN KEY (`project_id`)
    REFERENCES `shirt_projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_shirtmem_participant` FOREIGN KEY (`participant_id`)
    REFERENCES `participants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_shirtmem_paid` CHECK (`amount_paid` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- activity_logs (audit aktivitas admin) ----------
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL COMMENT 'mis: tambah_pembayaran, edit_pembayaran, hapus_pembayaran, ...',
  `description` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_logs_user` (`user_id`),
  KEY `idx_logs_created` (`created_at`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
