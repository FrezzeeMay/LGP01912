/* KasKita — shirts.js : preview upload + hitung target otomatis di form project. */
(function () {
  'use strict';
  function rupiah(n) { return 'Rp' + Number(n || 0).toLocaleString('id-ID'); }

  // Preview foto sebelum upload.
  document.querySelectorAll('input[type=file][data-preview]').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var img = document.getElementById(inp.getAttribute('data-preview'));
      var f = inp.files && inp.files[0];
      if (!img || !f) return;
      img.src = URL.createObjectURL(f);
      img.style.display = 'block';
    });
  });

  // Hitung target otomatis: harga × jumlah peserta.
  function bindCalc(scope) {
    if (!scope) return;
    var price = scope.querySelector('[name=price_per_person]');
    var count = scope.querySelector('[name=participant_count]');
    var out = scope.querySelector('[data-target-out]');
    if (!price || !count || !out) return;
    var fn = function () {
      var t = (Number(price.value) || 0) * (Number(count.value) || 0);
      out.textContent = rupiah(t);
    };
    price.addEventListener('input', fn); count.addEventListener('input', fn); fn();
  }
  document.querySelectorAll('[data-calc-target]').forEach(bindCalc);
})();
