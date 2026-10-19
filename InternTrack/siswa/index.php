<?php
require_once __DIR__ . '/../config/helpers.php';
require_login(['siswa']);
$pdo = db();
$me = siswa_saya();
$rk = rekap_kehadiran('WHERE s.id = ?', [$me['id']])[0];
$jml = $pdo->prepare('SELECT COUNT(*) FROM jurnal WHERE siswa_id=?'); $jml->execute([$me['id']]); $jml = (int) $jml->fetchColumn();
$sdh = $pdo->prepare('SELECT COUNT(*) FROM jurnal WHERE siswa_id=? AND tanggal=CURDATE()'); $sdh->execute([$me['id']]); $sdh = (int) $sdh->fetchColumn();
$abh = $pdo->prepare('SELECT COUNT(*) FROM absensi WHERE siswa_id=? AND tanggal=CURDATE()'); $abh->execute([$me['id']]); $abh = (int) $abh->fetchColumn();
$mn = $pdo->prepare('SELECT m.*, pb.nama AS pembimbing FROM monitoring m JOIN pembimbing pb ON pb.id=m.pembimbing_id WHERE m.siswa_id=? ORDER BY m.tanggal DESC, m.id DESC LIMIT 1'); $mn->execute([$me['id']]); $mn = $mn->fetch();
$jr = $pdo->prepare('SELECT * FROM jurnal WHERE siswa_id=? ORDER BY tanggal DESC, id DESC LIMIT 3'); $jr->execute([$me['id']]); $jr = $jr->fetchAll();
layout_start('Halo, ' . explode(' ', $me['nama'])[0], 'beranda', 'Catat kegiatan dan kehadiranmu setiap hari selama PKL.');
?>
<div class="grid3">
  <section class="card"><h2>Status PKL</h2><p style="margin:12px 0 6px"><?= badge_pkl($me['status']) ?></p>
    <p class="note"><?= $me['status'] ? e($me['perusahaan']) . ', ' . tgl($me['tanggal_mulai']) . ' sampai ' . tgl($me['tanggal_selesai']) : 'Admin belum menempatkanmu di perusahaan.' ?></p></section>
  <section class="card"><h2>Kehadiran</h2>
    <?php if ($rk['total']): ?><p class="big" style="margin:10px 0"><?= e($rk['persen']) ?>%</p><?= bar_persen($rk['persen'], $rk['status']) ?><p style="margin:10px 0 0"><?= badge_hadir($rk['status']) ?></p>
    <?php else: ?><p class="note" style="margin-top:12px">Belum ada data kehadiran.</p><?php endif; ?></section>
  <section class="card"><h2>Nilai monitoring terakhir</h2>
    <?php if ($mn): ?><p class="big" style="margin:10px 0"><?= (int) $mn['nilai'] ?></p><p class="note"><?= tgl($mn['tanggal']) ?>, <?= e($mn['pembimbing']) ?></p>
    <?php else: ?><p class="note" style="margin-top:12px">Pembimbing belum mencatat monitoring.</p><?php endif; ?></section>
</div>
<div class="grid2">
  <section class="card"><h2>Yang perlu diisi hari ini</h2>
    <p><?= $sdh ? 'Jurnal hari ini sudah terisi.' : 'Jurnal hari ini belum diisi.' ?></p>
    <p><?= $abh ? 'Kehadiran hari ini sudah tercatat.' : 'Kehadiran hari ini belum dicatat.' ?></p>
    <div class="actions"><a class="btn" href="<?= url('siswa/jurnal.php') ?>">Isi jurnal</a><a class="btn ghost" href="<?= url('siswa/absensi.php') ?>">Isi kehadiran</a></div></section>
  <section class="card"><h2>Jurnal terakhir <span class="note">(total <?= $jml ?>)</span></h2>
    <?php if (!$jr): ?><div class="empty">Belum ada jurnal. Mulai dari kegiatan hari ini.</div><?php endif;
    foreach ($jr as $x): ?><div class="entry"><div class="meta"><?= tgl($x['tanggal']) ?></div><?= e($x['kegiatan']) ?></div><?php endforeach; ?></section>
</div>
<?php layout_end();
