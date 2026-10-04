-- ============================================================
-- KasKita Online — schema Supabase (PostgreSQL)
-- Jalankan di: Supabase Dashboard → SQL Editor → New Query → Run.
-- Jalankan SEKALI untuk tabel + RLS. Bagian AKUN dijalankan
-- SETELAH membuat 3 user di Authentication (lihat README-ONLINE.md).
-- ============================================================

-- ---------- bersih (aman dijalankan ulang) ----------
drop policy if exists "pub_read" on public.participants;
drop policy if exists "super_write" on public.participants;
drop policy if exists "pub_read" on public.payments;
drop policy if exists "admin_write" on public.payments;
drop policy if exists "pub_read" on public.shirt_projects;
drop policy if exists "super_write" on public.shirt_projects;
drop policy if exists "pub_read" on public.shirt_participants;
drop policy if exists "admin_write" on public.shirt_participants;
drop policy if exists "auth_read_logs" on public.activity_logs;
drop policy if exists "auth_insert_logs" on public.activity_logs;
drop policy if exists "own_or_super_read" on public.profiles;
drop policy if exists "super_write" on public.profiles;

-- ---------- tabel ----------
create table if not exists public.profiles (
  id uuid primary key references auth.users(id) on delete cascade,
  email text unique not null,
  display_name text not null,
  role text not null default 'admin' check (role in ('admin','super_admin')),
  created_at timestamptz not null default now()
);

create table if not exists public.participants (
  id bigint generated always as identity primary key,
  name text not null check (char_length(name) between 1 and 100),
  status text not null default 'aktif' check (status in ('aktif','nonaktif')),
  shirt_size text null check (shirt_size in ('XS','S','M','L','XL','XXL','XXXL')),
  created_at timestamptz not null default now()
);
create index if not exists idx_participants_name on public.participants(name);

create table if not exists public.payments (
  id bigint generated always as identity primary key,
  participant_id bigint not null references public.participants(id) on delete cascade,
  amount int not null check (amount > 0 and amount <= 1000000000),
  payment_date date not null,
  note text null,
  created_by uuid null references public.profiles(id) on delete set null,
  updated_by uuid null references public.profiles(id) on delete set null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists idx_payments_participant on public.payments(participant_id);

create table if not exists public.shirt_projects (
  id bigint generated always as identity primary key,
  name text not null check (char_length(name) between 1 and 150),
  image text null,
  description text null,
  price_per_person int not null default 0 check (price_per_person >= 0),
  participant_count int not null default 0 check (participant_count >= 0),
  target_amount int not null default 0,
  status text not null default 'berjalan' check (status in ('berjalan','selesai','batal')),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.shirt_participants (
  id bigint generated always as identity primary key,
  project_id bigint not null references public.shirt_projects(id) on delete cascade,
  participant_id bigint not null references public.participants(id) on delete cascade,
  size text not null default 'L' check (size in ('XS','S','M','L','XL','XXL','XXXL')),
  amount_paid int not null default 0 check (amount_paid >= 0),
  status text not null default 'belum_bayar' check (status in ('belum_bayar','sebagian','lunas')),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique (project_id, participant_id)
);

create table if not exists public.activity_logs (
  id bigint generated always as identity primary key,
  user_id uuid null references public.profiles(id) on delete set null,
  action text not null,
  description text not null,
  created_at timestamptz not null default now()
);
create index if not exists idx_logs_created on public.activity_logs(created_at desc);

-- ---------- helper role (bypass RLS, cegah rekursi policy) ----------
create or replace function public.my_role()
returns text language sql security definer stable as
$$ select role from public.profiles where id = auth.uid() $$;

-- ---------- RLS ----------
alter table public.profiles enable row level security;
alter table public.participants enable row level security;
alter table public.payments enable row level security;
alter table public.shirt_projects enable row level security;
alter table public.shirt_participants enable row level security;
alter table public.activity_logs enable row level security;

-- USER (publik, tanpa login): hanya BOLEH MELIHAT kas & baju.
create policy "pub_read" on public.participants       for select using (true);
create policy "pub_read" on public.payments            for select using (true);
create policy "pub_read" on public.shirt_projects      for select using (true);
create policy "pub_read" on public.shirt_participants  for select using (true);

-- ADMIN (admin + super_admin): kelola pembayaran & anggota/setoran baju.
create policy "admin_write" on public.payments for all to authenticated
  using (public.my_role() in ('admin','super_admin'))
  with check (public.my_role() in ('admin','super_admin'));
create policy "admin_write" on public.shirt_participants for all to authenticated
  using (public.my_role() in ('admin','super_admin'))
  with check (public.my_role() in ('admin','super_admin'));

-- SUPER ADMIN SAJA: kelola peserta, project/target baju, dan profil akun.
create policy "super_write" on public.participants for all to authenticated
  using (public.my_role() = 'super_admin')
  with check (public.my_role() = 'super_admin');
create policy "super_write" on public.shirt_projects for all to authenticated
  using (public.my_role() = 'super_admin')
  with check (public.my_role() = 'super_admin');
create policy "super_write" on public.profiles for all to authenticated
  using (public.my_role() = 'super_admin')
  with check (public.my_role() = 'super_admin');
create policy "own_or_super_read" on public.profiles for select to authenticated
  using (auth.uid() = id or public.my_role() = 'super_admin');

-- LOG: admin boleh baca & mencatat; tidak boleh ubah/hapus.
create policy "auth_read_logs" on public.activity_logs for select to authenticated using (true);
create policy "auth_insert_logs" on public.activity_logs for insert to authenticated with check (true);

-- ---------- STORAGE foto baju (bucket publik, upload khusus admin) ----------
insert into storage.buckets (id, name, public)
values ('shirt-images', 'shirt-images', true)
on conflict (id) do nothing;

drop policy if exists "pub_read_shirt" on storage.objects;
drop policy if exists "admin_write_shirt" on storage.objects;
create policy "pub_read_shirt" on storage.objects for select using (bucket_id = 'shirt-images');
create policy "admin_write_shirt" on storage.objects for all to authenticated
  using (bucket_id = 'shirt-images' and public.my_role() in ('admin','super_admin'))
  with check (bucket_id = 'shirt-images' and public.my_role() in ('admin','super_admin'));

-- ============================================================
-- DATA CONTOH (jalankan SEKALI saja — pengulangan akan menduplikasi payments).
-- ============================================================
insert into public.participants (name, status, shirt_size) values
('Rayhan','aktif','L'),('Andi','aktif','M'),('Budi','aktif','XL'),
('Dimas','aktif','L'),('Fajar','aktif','M'),('Rizky','aktif','XL'),
('Reza','aktif','S'),('Aldi','aktif','L'),('Bagas','aktif','XXL'),('Farhan','aktif','M')
on conflict do nothing;

-- pembayaran contoh (pakai id peserta berdasarkan nama agar stabil)
insert into public.payments (participant_id, amount, payment_date, note)
select p.id, v.amount, v.pdate, v.note from public.participants p
join (values
  ('Rayhan',5000,'2026-10-04','Kas minggu 1'),('Rayhan',5000,'2026-10-05','Kas minggu 2'),
  ('Andi',10000,'2026-10-04','Kas 2 minggu'),('Dimas',5000,'2026-10-04',null),
  ('Fajar',5000,'2026-10-03',null),('Rizky',15000,'2026-10-02','Kas 3 minggu'),
  ('Reza',5000,'2026-10-04',null),('Aldi',10000,'2026-10-01',null),('Farhan',5000,'2026-10-04',null)
) as v(nama, amount, pdate, note) on v.nama = p.name
on conflict do nothing;

insert into public.shirt_projects (name, description, price_per_person, participant_count, target_amount, status)
values ('Baju Angkatan 2026','Kaos angkatan sablon depan-belakang, bahan cotton combed 30s.',75000,10,750000,'berjalan')
on conflict do nothing;

insert into public.shirt_participants (project_id, participant_id, size, amount_paid, status)
select sp.id, p.id, v.size, v.paid, v.st from public.shirt_projects sp cross join public.participants p
join (values
  ('Rayhan','L',75000,'lunas'),('Andi','M',75000,'lunas'),('Budi','XL',50000,'sebagian'),
  ('Dimas','L',75000,'lunas'),('Fajar','M',0,'belum_bayar'),('Rizky','XL',100000,'lunas'),
  ('Reza','S',25000,'sebagian'),('Aldi','L',75000,'lunas'),('Bagas','XXL',0,'belum_bayar'),('Farhan','M',75000,'lunas')
) as v(nama, size, paid, st) on v.nama = p.name
where sp.name = 'Baju Angkatan 2026'
on conflict (project_id, participant_id) do nothing;

-- ============================================================
-- AKUN — jalankan SETELAH membuat 3 user di Authentication
-- (dengan password kuat masing-masing — tidak ditulis di sini):
--   alpi019@gmail.com    → Admin A
--   naufal019@gmail.com  → Admin B
--   Rayhan019@gmail.com  → Super Admin
-- ============================================================
insert into public.profiles (id, email, display_name, role)
select id, 'alpi019@gmail.com', 'Admin A', 'admin' from auth.users where email = 'alpi019@gmail.com'
on conflict (id) do update set email='alpi019@gmail.com', display_name='Admin A', role='admin';

insert into public.profiles (id, email, display_name, role)
select id, 'naufal019@gmail.com', 'Admin B', 'admin' from auth.users where email = 'naufal019@gmail.com'
on conflict (id) do update set email='naufal019@gmail.com', display_name='Admin B', role='admin';

insert into public.profiles (id, email, display_name, role)
select id, 'Rayhan019@gmail.com', 'Super Admin', 'super_admin' from auth.users where email = 'Rayhan019@gmail.com'
on conflict (id) do update set email='Rayhan019@gmail.com', display_name='Super Admin', role='super_admin';
