<?php
require_once __DIR__ . '/../config/helpers.php';
require_login(['siswa']);
$me = siswa_saya();
$progress = 0;
if ($me['status'] === 'Selesai') { $progress = 100; }
elseif ($me['status'] === 'Aktif') {
    $a = strtotime($me['tanggal_mulai']); $b = strtotime($me['tanggal_selesai']);
    $progress = $b > $a ? max(0, min(100, round((time() - $a) / ($b - $a) * 100))) : 0;
}
layout_start('Profil dan tempat PKL', 'profil');
?>
<div class="grid2">
  <section class="card"><h2>Profil</h2>
    <dl class="kv"><dt>NIS</dt><dd><?= e($me['nis']) ?></dd><dt>Nama</dt><dd><?= e($me['nama']) ?></dd><dt>Kelas</dt><dd><?= e($me['kelas']) ?></dd><dt>Jurusan</dt><dd><?= e($me['jurusan']) ?></dd><dt>No. HP</dt><dd><?= e($me['no_hp'] ?: '-') ?></dd></dl></section>
  <section class="card"><h2>Tempat PKL</h2>
    <?php if (!$me['status']): ?><div class="empty">Kamu belum ditempatkan. Tanyakan ke admin atau guru pembimbing.</div><?php else: ?>
    <dl class="kv"><dt>Perusahaan</dt><dd><?= e($me['perusahaan']) ?></dd><dt>Alamat</dt><dd><?= e($me['pr_alamat']) ?></dd><dt>Bidang</dt><dd><?= e($me['pr_bidang']) ?></dd><dt>Kontak</dt><dd><?= e($me['pr_kontak']) ?></dd>
    <dt>Pembimbing</dt><dd><?= e($me['pembimbing']) ?> <span class="note"><?= e($me['pb_hp'] ?: '') ?></span></dd></dl><?php endif; ?></section>
</div>
<section class="card"><h2>Status PKL</h2>
  <p><?= badge_pkl($me['status']) ?></p>
  <?php if ($me['status']): ?><p class="note">Periode <?= tgl($me['tanggal_mulai']) ?> sampai <?= tgl($me['tanggal_selesai']) ?></p>
  <div class="bar"><span class="f-baik" data-w="<?= $progress ?>"></span></div><p class="note" style="margin-top:6px">Perjalanan PKL: <?= $progress ?>%</p><?php endif; ?></section>
<?php layout_end();
