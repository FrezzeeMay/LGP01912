/* KasKita — payments.js : isi modal edit dari data-* baris transaksi. */
(function () {
  'use strict';
  // Delegasi klik tombol edit (ada di dalam modal riwayat yang dirender dinamis).
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('[data-edit-payment]');
    if (!b) return;
    var f = document.getElementById('formEdit');
    if (!f) return;
    f.payment_id.value = b.getAttribute('data-id') || '';
    f.amount.value = b.getAttribute('data-amount') || '';
    f.payment_date.value = b.getAttribute('data-date') || '';
    f.note.value = b.getAttribute('data-note') || '';
    document.getElementById('editInfo').textContent =
      'Mengedit pembayaran ' + b.getAttribute('data-pname') + ' sebesar Rp' +
      Number(b.getAttribute('data-amount') || 0).toLocaleString('id-ID');
    window.openModal('Edit Pembayaran', document.getElementById('tplEdit').innerHTML);
    // Pindahkan nilai ke form hasil klon (karena innerHTML mengklon, isi ulang via name).
    var mf = document.querySelector('#modalBody form');
    if (mf) {
      mf.payment_id.value = b.getAttribute('data-id') || '';
      mf.amount.value = b.getAttribute('data-amount') || '';
      mf.payment_date.value = b.getAttribute('data-date') || '';
      mf.note.value = b.getAttribute('data-note') || '';
    }
  });
})();
