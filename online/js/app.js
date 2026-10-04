/* KasKita Online — js/app.js : client Supabase + helper + auth guard. Dipakai semua halaman. */
const sb = supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

/* ---------- helper tampilan ---------- */
const rupiah = (n) => 'Rp' + Number(n || 0).toLocaleString('id-ID');
const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const tgl = (d) => { if (!d) return '-'; const x = new Date(d.length <= 10 ? d + 'T00:00:00' : d); return isNaN(x) ? esc(d) : x.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }); };
const tglJam = (d) => { if (!d) return '-'; const x = new Date(d); return isNaN(x) ? esc(d) : x.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' + x.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }); };
const shirtLabel = (s) => s === 'lunas' ? 'Lunas' : s === 'sebagian' ? 'Sebagian' : 'Belum Bayar';
const shirtBadge = (s) => s === 'lunas' ? 'ok' : s === 'sebagian' ? 'warn' : 'bad';

function toast(msg, type = 'success') {
  const box = document.getElementById('toasts') || document.body;
  const el = document.createElement('div');
  el.className = 'alert ' + type;
  el.textContent = msg;
  if (box.id === 'toasts') box.prepend(el); else document.querySelector('.content')?.prepend(el);
  setTimeout(() => el.remove(), 4500);
}

/* ---------- modal generik ---------- */
function openModal(title, html) {
  document.getElementById('modalTitle').textContent = title;
  const b = document.getElementById('modalBody');
  if (typeof html === 'string') b.innerHTML = html; else { b.innerHTML = ''; b.appendChild(html); }
  document.getElementById('modalBackdrop').classList.add('open');
}
function closeModal() { document.getElementById('modalBackdrop')?.classList.remove('open'); }

/* ---------- auth ---------- */
async function currentProfile() {
  const { data: { session } } = await sb.auth.getSession();
  if (!session) return null;
  const { data } = await sb.from('profiles').select('*').eq('id', session.user.id).single();
  return data || null;
}
// Halaman admin wajib panggil ini. roles cth: ['admin','super_admin'] atau ['super_admin'].
async function guard(roles) {
  const p = await currentProfile();
  if (!p) { location.href = '../login.html'; return null; }
  if (!roles.includes(p.role)) {
    document.querySelector('.content').innerHTML = '<div class="alert error">403 — Role <b>' + esc(p.role) + '</b> tidak diizinkan membuka halaman ini.</div>';
    throw new Error('forbidden');
  }
  // Tampilkan nama + role di sidebar/topbar
  document.querySelectorAll('[data-user-name]').forEach((e) => (e.textContent = p.display_name));
  document.querySelectorAll('[data-user-role]').forEach((e) => (e.textContent = p.role === 'super_admin' ? 'Super Admin' : 'Admin'));
  document.querySelectorAll('[data-user-mail]').forEach((e) => (e.textContent = p.email));
  document.querySelectorAll('.only-super').forEach((e) => (e.style.display = p.role === 'super_admin' ? '' : 'none'));
  return p;
}
async function logout() { await sb.auth.signOut(); location.href = '../login.html'; }

async function logActivity(action, description) {
  const { data: { session } } = await sb.auth.getSession();
  if (!session) return;
  await sb.from('activity_logs').insert({ user_id: session.user.id, action, description });
}

/* ---------- chrome UI (dipakai semua halaman) ---------- */
document.addEventListener('DOMContentLoaded', () => {
  const t = document.getElementById('navToggle');
  if (t) t.addEventListener('click', () => document.body.classList.toggle('nav-open'));
  document.getElementById('scrim')?.addEventListener('click', () => document.body.classList.remove('nav-open'));
  const tick = () => {
    const el = document.getElementById('clock');
    if (el) { const d = new Date(); el.textContent = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ' · ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }); }
  };
  tick(); setInterval(tick, 30000);
  document.getElementById('modalBackdrop')?.addEventListener('click', (e) => { if (e.target.id === 'modalBackdrop') closeModal(); });
  document.querySelectorAll('[data-close-modal]').forEach((b) => b.addEventListener('click', closeModal));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });
  document.querySelectorAll('[data-logout]').forEach((b) => b.addEventListener('click', logout));
});
