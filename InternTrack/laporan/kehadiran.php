<?php
// Laporan 2: Rekap kehadiran + status (memakai ALGORITMA 3 pada config/helpers.php)
require_once __DIR__ . '/../config/helpers.php';
require_login(['admin', 'pembimbing']);
$pdo = db();
$fk = $_GET['kelas'] ?? '';
$rows = rekap_kehadiran($fk !== '' ? 'WHERE s.kelas = ?' : '', $fk !== '' ? [$fk] : []);
$kelas = $pdo->query('SELECT DISTINCT kelas FROM siswa ORDER BY kelas')->fetchAll(PDO::FETCH_COLUMN);
layout_start('Laporan kehadiran', 'laporan', 'Dicetak pada ' . hari_ini_panjang() . '.');
laporan_tabs('kehadiran');
?>
<form class="card filters" method="get">
  <div class="field"><label for="kelas">Kelas</label><select id="kelas" name="kelas"><?= opt_list(array_combine($kelas, $kelas), $fk, 'Semua kelas') ?></select></div>
  <div class="actions"><button class="btn" type="submit">Terapkan</button><button class="btn ghost" type="button" onclick="window.print()">Cetak laporan</button></div>
</form>
<section class="card">
<div class="tablewrap"><table class="tbl"><thead><tr><th>No</th><th>NIS</th><th>Nama</th><th>Kelas</th><th class="num">Total hari</th><th class="num">Hadir</th><th class="num">Izin</th><th class="num">Sakit</th><th class="num">Alpa</th><th class="num">Persentase</th><th>Status</th></tr></thead><tbody>
<?php foreach ($rows as $i => $r): ?>
  <tr><td><?= $i + 1 ?></td><td><?= e($r['nis']) ?></td><td><?= e($r['nama']) ?></td><td><?= e($r['kelas']) ?></td><td class="num"><?= (int) $r['total'] ?></td><td class="num"><?= (int) $r['hadir'] ?></td><td class="num"><?= (int) $r['izin'] ?></td><td class="num"><?= (int) $r['sakit'] ?></td><td class="num"><?= (int) $r['alpa'] ?></td>
  <td class="num"><?= $r['total'] ? e($r['persen']) . '%' : '-' ?></td><td><?= $r['total'] ? badge_hadir($r['status']) : '<span class="badge b-none">Belum ada data</span>' ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<p class="note" style="margin-top:12px">Aturan status: 90% atau lebih Sangat Baik; 75% sampai 89% Baik; 60% sampai 74% Perlu Perhatian; di bawah 60% Perlu Pembinaan.</p>
</section>
<?php layout_end();
