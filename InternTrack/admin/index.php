<?php
require_once __DIR__ . '/../config/helpers.php';
require_login(['admin', 'pembimbing']);
$pdo = db();
$hit = function (string $sql) use ($pdo): int { return (int) $pdo->query($sql)->fetchColumn(); };

// Semua angka dihitung dari database, tidak ditulis statis
$totalSiswa  = $hit('SELECT COUNT(*) FROM siswa');
$totalPersh  = $hit('SELECT COUNT(*) FROM perusahaan');
$totalPemb   = $hit('SELECT COUNT(*) FROM pembimbing');
$aktif       = $hit("SELECT COUNT(*) FROM penempatan WHERE status='Aktif'");
$selesai     = $hit("SELECT COUNT(*) FROM penempatan WHERE status='Selesai'");
$belum       = $hit("SELECT COUNT(*) FROM penempatan WHERE status='Belum Mulai'");
$jurnalHari  = $hit('SELECT COUNT(*) FROM jurnal WHERE tanggal = CURDATE()');
$rekap       = rekap_kehadiran();
$perhatian   = array_values(array_filter($rekap, function ($r) { return $r['total'] > 0 && $r['persen'] < 75; }));
$totalPn     = max(1, $aktif + $selesai + $belum);

$jurnalBaru = $pdo->query('SELECT j.tanggal, j.kegiatan, s.nama FROM jurnal j JOIN siswa s ON s.id=j.siswa_id ORDER BY j.tanggal DESC, j.id DESC LIMIT 6')->fetchAll();

layout_start('Dashboard', 'dashboard', 'Ringkasan kegiatan PKL hari ini, dihitung langsung dari database.');
?>
<div class="grid2">
  <section class="card ledger">
    <div class="head">INTERNTRACK</div>
    <div class="row"><span>Total siswa PKL</span><i></i><b><?= $totalSiswa ?></b></div>
    <div class="row"><span>Perusahaan</span><i></i><b><?= $totalPersh ?></b></div>
    <div class="row"><span>Pembimbing</span><i></i><b><?= $totalPemb ?></b></div>
    <div class="row"><span>PKL aktif</span><i></i><b><?= $aktif ?></b></div>
    <div class="row"><span>PKL selesai</span><i></i><b><?= $selesai ?></b></div>
    <div class="rule"></div>
    <div class="row"><span>Jurnal hari ini</span><i></i><b><?= $jurnalHari ?></b></div>
    <div class="row <?= count($perhatian) ? 'hot' : '' ?>"><span>Siswa perlu perhatian</span><i></i><b><?= count($perhatian) ?></b></div>
  </section>

  <section class="card">
    <h2>Posisi penempatan PKL</h2>
    <div class="seg" role="img" aria-label="Aktif <?= $aktif ?>, selesai <?= $selesai ?>, belum mulai <?= $belum ?>">
      <span style="width:<?= $aktif / $totalPn * 100 ?>%;background:var(--blue)"></span>
      <span style="width:<?= $selesai / $totalPn * 100 ?>%;background:var(--ink)"></span>
      <span style="width:<?= $belum / $totalPn * 100 ?>%;background:var(--hv)"></span>
    </div>
    <div class="legend">
      <span><i style="background:var(--blue)"></i>Aktif <?= $aktif ?></span>
      <span><i style="background:var(--ink)"></i>Selesai <?= $selesai ?></span>
      <span><i style="background:var(--hv)"></i>Belum mulai <?= $belum ?></span>
    </div>
    <p class="note" style="margin-top:16px">Siswa yang belum ditempatkan: <?= max(0, $totalSiswa - ($aktif + $selesai + $belum)) ?> dari <?= $totalSiswa ?> siswa.</p>
    <div class="actions" style="margin-top:12px">
      <a class="btn" href="<?= url('admin/penempatan.php?aksi=form') ?>">Tempatkan siswa</a>
      <a class="btn ghost" href="<?= url('laporan/siswa.php') ?>">Buka laporan</a>
    </div>
  </section>
</div>

<div class="grid2">
  <section class="card">
    <h2>Siswa yang perlu perhatian</h2>
    <?php if (!$perhatian): ?>
      <div class="empty">Semua siswa dengan data kehadiran berada di atas 75 persen.</div>
    <?php else: ?>
      <div class="tablewrap"><table class="tbl"><thead><tr><th>Nama</th><th>Kelas</th><th class="num">Hadir</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($perhatian as $r): ?>
        <tr><td><?= e($r['nama']) ?></td><td><?= e($r['kelas']) ?></td><td class="num"><?= e($r['persen']) ?>%</td><td><?= badge_hadir($r['status']) ?></td></tr>
      <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
  </section>
  <section class="card">
    <h2>Jurnal terbaru</h2>
    <?php if (!$jurnalBaru): ?><div class="empty">Belum ada jurnal yang masuk.</div>
    <?php else: foreach ($jurnalBaru as $j): ?>
      <div class="entry"><div class="meta"><?= e($j['nama']) ?>, <?= tgl($j['tanggal']) ?></div><?= e($j['kegiatan']) ?></div>
    <?php endforeach; endif; ?>
    <a class="btn ghost sm" href="<?= url('admin/jurnal.php') ?>">Lihat semua aktivitas</a>
  </section>
</div>
<?php layout_end();
