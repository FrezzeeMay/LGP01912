# KasKita — Sistem Kas & Pembuatan Baju (Database: Supabase, online 24 jam)

Full-stack: HTML5 + CSS3 + Vanilla JS, PHP 8+, **PostgreSQL via Supabase**.
PHP di XAMPP/Laragon hanya jadi backend — datanya tinggal di cloud Supabase,
jadi website bisa diakses kapan saja tanpa PC menyala (saat deploy hosting).

## 0. Syarat sekali saja — aktifkan driver Postgres di XAMPP

1. Buka **XAMPP Control Panel sebagai Administrator** tidak perlu; cukup:
   buka file `C:\xampp\php\php.ini` **sebagai Administrator**
   (klik kanan Notepad → Run as administrator → Open file).
2. Cari baris `;extension=pdo_pgsql`, hapus titik-komanya menjadi:
   `extension=pdo_pgsql`
3. Simpan, lalu **Restart Apache**.
4. Tes: `C:\xampp\php\php.exe -m` harus memunculkan `pdo_pgsql`.

## 1. Buat project Supabase (gratis)

1. Buka https://supabase.com → Sign up/login → **New project**.
2. Isi nama (mis. `kaskita`), isi **Database Password** (catat baik-baik!),
   pilih region terdekat (Singapore), Create.
3. Tunggu ±2 menit sampai project siap.

## 2. Buat tabel (SQL Editor)

1. Di dashboard Supabase → **SQL Editor** → **New query**.
2. Tempel SELURUH isi `database/supabase_schema.sql` → **Run**.
3. New query lagi → tempel `database/supabase_seed.sql` → **Run**.
4. Cek di **Table Editor**: tabel `users` harus berisi 4 akun.

## 3. Sambungkan PHP ke Supabase (via `.env`, tanpa password di source code)

1. Dashboard → **Settings → Database** → bagian **Connection string** →
   pilih **URI**, salin. Bentuknya:
   `postgresql://postgres.xxxxx:PASSWORD@aws-0-...pooler.supabase.com:6543/postgres`
2. Buka file `.env` di root project (sudah dibuatkan, **tidak ikut ke GitHub**),
   isi 5 nilainya:
   - `DB_HOST` → host pooler (`...pooler.supabase.com`)
    - `DB_PORT` → `5432` (mode session pooler)
   - `DB_NAME` → `postgres`
   - `DB_USER` → `postgres.xxxxx`
   - `DB_PASSWORD` → database password langkah 1 (bukan placeholder)
3. Kalau `.env` belum diisi / salah, website menampilkan pesan jelas
   ("Database belum dikonfigurasi" + nama key yang kurang) —
   bukan error mentah.
4. Di hosting (Render/Railway), isi key yang **sama persis**
   (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`)
   sebagai Environment Variable — file `.env` tidak perlu di-upload.
5. Contoh format selalu tersedia di `.env.example` (yang ini aman di-commit).

## 4. Jalankan website lokal

- Copy folder ke `C:\xampp\htdocs\kaskita`, start Apache,
  buka **http://localhost/kaskita/login.php**.
- (Laragon: taruh di `www\kaskita`, buka `http://kaskita.test/login.php`.)
- Foto desain baju tetap tersimpan lokal di `uploads/shirts/`
  (hanya data teks/angka yang di Supabase).

## 5. Akun (seed, password di-hash)

| Login (email/username)   | Password     | Role        |
| ------------------------ | ------------ | ----------- |
| `alpi019@gmail.com`      | `LGP019`     | Admin (Alpi) |
| `naufal019@gmail.com`    | `LGP019`     | Admin (Naufal) |
| `Rayhan019@gmai.com`    | `Apalah019`  | Super Admin |
| `viewer@kaskita.local`   | `Viewer123!` | User (lihat saja) |

> Catatan: email super admin ditulis persis sesuai permintaan
> (`Rayhan019@gmai.com`). Kalau seharusnya `gmail.com`, kabari saja —
> tinggal update 1 baris di seed + tabel `users`.

## 6. Cara kerja role

- **Admin (Alpi, Naufal)**: dashboard, tambah/edit/hapus pembayaran
  (tercatat penginput + activity log), lihat peserta & baju, setor/ubah
  anggota baju. Tidak bisa: CRUD peserta, kelola project/target baju,
  kelola akun.
- **Super Admin**: semua akses admin + CRUD peserta, kelola project &
  target baju, kelola akun (bisa buat akun role apa pun), lihat seluruh
  aktivitas + siapa mengubah apa.
- **User**: login, **hanya melihat** (dashboard, tabel kas + riwayat,
  peserta, baju + progress). Semua tombol tulis disembunyikan DAN
  ditolak di backend (`can_write()` / `require_write()` + 403 di API).
  Menu Aktivitas & Kelola Admin tidak tampil untuk role ini.

## 7. Struktur database (Supabase Postgres)

- `users` — id, username, email, password (hash), display_name,
  role (`admin`/`super_admin`/`user`), created_at
- `participants` — id, name, status, shirt_size, created_at
- `payments` — id, participant_id (FK CASCADE), amount, payment_date, note,
  created_by, updated_by, created_at, updated_at (via trigger)
- `shirt_projects` — id, name, image (path lokal), description,
  price_per_person, participant_count, target_amount (harga × peserta,
  dihitung PHP), status
- `shirt_participants` — id, (project_id, participant_id) unik,
  size, amount_paid, status (dihitung: 0=belum, &lt;harga=sebagian,
  ≥harga=lunas)
- `activity_logs` — id, user_id, action, description, created_at

File MySQL lama (`schema.sql`, `seed.sql`) masih ada sebagai arsip,
tapi yang dipakai sekarang adalah `supabase_schema.sql` + `supabase_seed.sql`.

## 8. Keamanan

Prepared statements (PDO pgsql, emulate off, `sslmode=require`),
`password_hash`/`password_verify`, session + regenerasi ID saat login,
RBAC backend, validasi server-side, upload JPG/JPEG/PNG/WebP ≤ 2 MB
+ cek MIME + nama acak, CSRF di semua form POST & API,
`htmlspecialchars` di semua output.
