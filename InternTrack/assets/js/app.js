// InternTrack - interaksi sisi klien: validasi form, konfirmasi hapus, animasi bar, tampilkan password
document.addEventListener('DOMContentLoaded', function () {
  // satu momen animasi: bar persentase tumbuh saat halaman terbuka
  document.querySelectorAll('.bar > span[data-w]').forEach(function (el) {
    requestAnimationFrame(function () { el.style.width = el.dataset.w + '%'; });
  });

  // konfirmasi sebelum menghapus
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
  });

  // pilihan yang langsung dikirim (mis. ubah status penempatan)
  document.querySelectorAll('select[data-autosubmit]').forEach(function (s) {
    s.addEventListener('change', function () { s.form.submit(); });
  });

  // pesan sukses hilang sendiri
  var al = document.querySelector('.alert[data-auto]');
  if (al) setTimeout(function () { al.style.display = 'none'; }, 6000);

  // tampilkan/sembunyikan password
  var t = document.getElementById('togglePw');
  if (t) t.addEventListener('click', function () {
    var p = document.getElementById('password');
    p.type = p.type === 'password' ? 'text' : 'password';
    t.textContent = p.type === 'password' ? 'Tampilkan' : 'Sembunyikan';
  });

  // validasi kolom wajib sebelum form dikirim (server tetap memeriksa ulang)
  document.querySelectorAll('form[data-validate]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      var bad = false;
      f.querySelectorAll('[required]').forEach(function (i) {
        var box = i.closest('.field');
        var m = box && box.querySelector('.js-err');
        if (!(i.value || '').trim()) {
          bad = true;
          if (box) {
            box.classList.add('err');
            if (!m) { m = document.createElement('div'); m.className = 'msg-err js-err'; box.appendChild(m); }
            var wajibPilih = i.tagName === 'SELECT' ? ' wajib dipilih.' : ' wajib diisi.';
            m.textContent = (i.dataset.label || 'Kolom ini') + wajibPilih;
          }
        } else if (box) {
          box.classList.remove('err');
          if (m) m.remove();
        }
      });
      if (bad) {
        e.preventDefault();
        var first = f.querySelector('.field.err input, .field.err select, .field.err textarea');
        if (first) first.focus();
      }
    });
  });
});
