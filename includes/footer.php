<?php // includes/footer.php — penutup layout + script global. ?>
    </main>
    <footer class="foot">KasKita · Sistem Kas &amp; Pembuatan Baju · Data tersimpan di database MySQL</footer>
  </div>
</div>
<div class="modal-backdrop" id="modalBackdrop">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-head"><h3 id="modalTitle">Detail</h3><button class="icon-btn" data-close-modal aria-label="Tutup">✕</button></div>
    <div class="modal-body" id="modalBody"></div>
  </div>
</div>
<script src="<?= e($base ?? '') ?>assets/js/app.js"></script>
<?php if (!empty($extraJs)): foreach ((array)$extraJs as $js): ?>
<script src="<?= e(($base ?? '') . $js) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
