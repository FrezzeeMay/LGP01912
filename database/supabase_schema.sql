-- ============================================================
-- KasKita — database/supabase_schema.sql  (PostgreSQL / Supabase)
-- Cara pakai: Supabase Dashboard → SQL Editor → New query →
--   tempel SELURUH isi file ini → Run. Lalu jalankan supabase_seed.sql.
-- File ini aman dijalankan ulang (IF NOT EXISTS).
-- ============================================================

-- ---------- users (akun login: admin, super_admin, user) ----------
CREATE TABLE IF NOT EXISTS users (
  id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL, -- hash via password_hash(), bukan plaintext
  display_name VARCHAR(100) NOT NULL,
  role TEXT NOT NULL DEFAULT 'admin' CHECK (role IN ('admin', 'super_admin', 'user')),
  must_change_password BOOLEAN NOT NULL DEFAULT TRUE, -- TRUE = wajib ganti password saat login berikut
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ---------- participants (peserta kas) ----------
CREATE TABLE IF NOT EXISTS participants (
  id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  status TEXT NOT NULL DEFAULT 'aktif' CHECK (status IN ('aktif', 'nonaktif')),
  shirt_size TEXT NULL CHECK (shirt_size IN ('XS','S','M','L','XL','XXL','XXXL')),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_participants_name ON participants (name);
CREATE INDEX IF NOT EXISTS idx_participants_status ON participants (status);

-- ---------- payments (setiap transaksi kas) ----------
CREATE TABLE IF NOT EXISTS payments (
  id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  participant_id INTEGER NOT NULL REFERENCES participants (id) ON DELETE CASCADE ON UPDATE CASCADE,
  amount INTEGER NOT NULL CHECK (amount > 0),
  payment_date DATE NOT NULL,
  note TEXT NULL,
  created_by INTEGER NULL REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
  updated_by INTEGER NULL REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_payments_participant ON payments (participant_id);
CREATE INDEX IF NOT EXISTS idx_payments_date ON payments (payment_date);

-- ---------- shirt_projects (project baju gabungan) ----------
CREATE TABLE IF NOT EXISTS shirt_projects (
  id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  image VARCHAR(255) NULL, -- path file lokal di uploads/shirts/
  description TEXT NULL,
  price_per_person INTEGER NOT NULL DEFAULT 0 CHECK (price_per_person >= 0),
  participant_count INTEGER NOT NULL DEFAULT 0 CHECK (participant_count >= 0),
  target_amount INTEGER NOT NULL DEFAULT 0, -- otomatis = price * count (dihitung PHP)
  status TEXT NOT NULL DEFAULT 'berjalan' CHECK (status IN ('berjalan','selesai','batal')),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ---------- shirt_participants (anggota per project baju) ----------
CREATE TABLE IF NOT EXISTS shirt_participants (
  id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  project_id INTEGER NOT NULL REFERENCES shirt_projects (id) ON DELETE CASCADE ON UPDATE CASCADE,
  participant_id INTEGER NOT NULL REFERENCES participants (id) ON DELETE CASCADE ON UPDATE CASCADE,
  size TEXT NOT NULL DEFAULT 'L' CHECK (size IN ('XS','S','M','L','XL','XXL','XXXL')),
  amount_paid INTEGER NOT NULL DEFAULT 0 CHECK (amount_paid >= 0),
  status TEXT NOT NULL DEFAULT 'belum_bayar' CHECK (status IN ('belum_bayar','sebagian','lunas')),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  UNIQUE (project_id, participant_id)
);
CREATE INDEX IF NOT EXISTS idx_shirtmem_project ON shirt_participants (project_id);

-- ---------- activity_logs (audit aktivitas admin) ----------
CREATE TABLE IF NOT EXISTS activity_logs (
  id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  user_id INTEGER NULL REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
  action VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_logs_user ON activity_logs (user_id);
CREATE INDEX IF NOT EXISTS idx_logs_created ON activity_logs (created_at);

-- ---------- Trigger: updated_at otomatis (pengganti ON UPDATE MySQL) ----------
CREATE OR REPLACE FUNCTION touch_updated_at()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_payments_touch ON payments;
CREATE TRIGGER trg_payments_touch BEFORE UPDATE ON payments
FOR EACH ROW EXECUTE FUNCTION touch_updated_at();

DROP TRIGGER IF EXISTS trg_projects_touch ON shirt_projects;
CREATE TRIGGER trg_projects_touch BEFORE UPDATE ON shirt_projects
FOR EACH ROW EXECUTE FUNCTION touch_updated_at();

DROP TRIGGER IF EXISTS trg_shirtmem_touch ON shirt_participants;
CREATE TRIGGER trg_shirtmem_touch BEFORE UPDATE ON shirt_participants
FOR EACH ROW EXECUTE FUNCTION touch_updated_at();
