/* KasKita — assets/js/app.js : sidebar mobile, modal generik, jam, konfirmasi hapus. */
(function () {
  'use strict';

  // Sidebar mobile
  var t = document.getElementById('navToggle');
  var scrim = document.getElementById('scrim');
  if (t) t.addEventListener('click', function () { document.body.classList.toggle('nav-open'); });
  if (scrim) scrim.addEventListener('click', function () { document.body.classList.remove('nav-open'); });

  // Jam live di topbar
  function tick() {
    var el = document.getElementById('clock');
    if (!el) return;
    var d = new Date();
    el.textContent = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) +
      ' · ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
  }
  tick(); setInterval(tick, 30000);

  // Modal generik: panggil openModal(title, html)
  var backdrop = document.getElementById('modalBackdrop');
  var mTitle = document.getElementById('modalTitle');
  var mBody = document.getElementById('modalBody');
  window.openModal = function (title, html) {
    if (!backdrop) return;
    mTitle.textContent = title;
    if (typeof html === 'string') mBody.innerHTML = html;
    else { mBody.innerHTML = ''; mBody.appendChild(html); }
    backdrop.classList.add('open');
  };
  window.closeModal = function () { if (backdrop) backdrop.classList.remove('open'); };
  if (backdrop) {
    backdrop.addEventListener('click', function (ev) { if (ev.target === backdrop) window.closeModal(); });
    backdrop.querySelectorAll('[data-close-modal]').forEach(function (b) {
      b.addEventListener('click', window.closeModal);
    });
  }
  document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') window.closeModal(); });

  // Tombol "Lihat Riwayat": isi modal dari <template> yang dirender server (tanpa fetch).
  document.querySelectorAll('[data-history-of]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var tpl = document.getElementById('history-' + btn.getAttribute('data-history-of'));
      window.openModal('Riwayat Pembayaran — ' + btn.getAttribute('data-history-name'), tpl ? tpl.innerHTML : '<p class="muted">Tidak ada riwayat.</p>');
    });
  });

  // Konfirmasi hapus generik (form dengan data-confirm).
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (ev) {
      if (!window.confirm(f.getAttribute('data-confirm'))) ev.preventDefault();
    });
  });

  // Filter tabel client-side (input data-table-filter="idTabel").
  document.querySelectorAll('[data-table-filter]').forEach(function (input) {
    input.addEventListener('input', function () {
      var q = input.value.toLowerCase();
      var rows = document.querySelectorAll('#' + input.getAttribute('data-table-filter') + ' tbody tr');
      rows.forEach(function (r) {
        r.style.display = r.textContent.toLowerCase().indexOf(q) === -1 ? 'none' : '';
      });
    });
  });
})();
