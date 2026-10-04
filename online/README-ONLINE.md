# KasKita Online — database di Supabase (online 24 jam)

Folder `online/` adalah versi web yang databasenya memakai **Supabase**
(PostgreSQL + Auth + Storage) sehingga aktif 24 jam tanpa XAMPP.
File web-nya (HTML/CSS/JS) ditaruh di hosting statis gratis (Netlify/Vercel —
juga 24 jam). Versi PHP/MySQL lama tetap ada dan tidak diubah.

**Akun (dibuat di langkah 3–4):**

| Email | Password | Role |
|---|---|---|
| `alpi019@gmail.com` | `LGP019` | Admin |
| `naufal019@gmail.com` | `LGP019` | Admin |
| `Rayhan019@gmail.com` | `Apalah019` | Super Admin |

> Catatan: kamu menulis `Rayhan019@gmai.com` — saya asumsikan typo dari
> `gmail.com`. Kalau memang `gmai.com`, ganti di `schema.sql` bagian AKUN.

**User/peserta: hanya bisa MELIHAT** — buka `index.html` tanpa login
(read-only, dijamin oleh RLS policy `pub_read`: SELECT saja, tanpa login).

## Langkah 1 — Buat project Supabase (gratis)

1. Buka https://supabase.com → Sign up/login → **New project**.
2. Isi nama (mis. `kaskita`), password database (simpan!), region **Singapore**.
3. Tunggu ±2 menit sampai project siap.

## Langkah 2 — Jalankan schema.sql

1. Di dashboard Supabase → **SQL Editor** → **New query**.
2. Copy seluruh isi `online/schema.sql` → paste → **Run**.
3. Ini membuat tabel (`profiles`, `participants`, `payments`,
   `shirt_projects`, `shirt_participants`, `activity_logs`), **RLS**,
   bucket Storage `shirt-images`, dan data contoh.
4. Bagian **AKUN** di file yang sama **jangan dulu** — jalankan di langkah 4.

## Langkah 3 — Buat 3 user login

1. Menu **Authentication → Users → Add user → Create new user**.
2. Buat satu per satu (centang **Auto Confirm User**):
   - `alpi019@gmail.com` / `LGP019`
   - `naufal019@gmail.com` / `LGP019`
   - `Rayhan019@gmail.com` / `Apalah019`

## Langkah 4 — Pasang role akun

1. Kembali ke **SQL Editor**, blokir hanya bagian **AKUN** di `schema.sql`
   (3 perintah `insert into public.profiles …`) → **Run**.
2. Cek: **Table Editor → profiles** → harus ada 3 baris dengan role benar.

## Langkah 5 — Hubungkan web ke Supabase

1. **Project Settings → API**: salin **Project URL** dan **anon public key**.
2. Buka `online/js/config.js`, isi `SUPABASE_URL` dan `SUPABASE_ANON_KEY`.

## Langkah 6 — Online-kan webnya (pilih satu, gratis, 24 jam)

- **Termudah — Netlify Drop**: buka https://app.netlify.com/drop → drag
  folder `online/` → dapat URL `https://xxx.netlify.app` (selamanya online).
- **Vercel**: https://vercel.com → Add New → Project → upload folder `online/`.
- **GitHub Pages / Cloudflare Pages**: upload folder, aktifkan Pages.

Halaman publik = URL utama (`index.html`). Login admin = `.../login.html`.

## Langkah 7 — Tes

1. Buka halaman publik: tabel kas + baju tampil **tanpa login**.
2. Login sebagai `alpi019@gmail.com` → tambah pembayaran → cek total berubah
   dan nama tercatat sebagai penginput.
3. Login sebagai `Rayhan019@gmail.com` → menu **Kelola Admin** muncul,
   bisa tambah peserta & buat project baju. Admin biasa tidak melihat menu itu
   (dan diblokir juga di database oleh RLS).

## Keamanan versi online

- Role dicek di **browser** (UX) DAN di **database** (RLS `admin_write` /
  `super_write` + fungsi `my_role()`) — user tidak bisa bypass lewat console.
- Anon key aman dipajang di frontend karena RLS membatasi:
  publik hanya SELECT kas/baju; tulis wajib login + role cocok.
- Foto baju: publik bisa lihat, hanya admin bisa upload (policy Storage).
