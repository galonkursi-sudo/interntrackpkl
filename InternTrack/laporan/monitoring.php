<?php
// Laporan 3: Monitoring pembimbing
require_once __DIR__ . '/../config/helpers.php';
require_login(['admin', 'pembimbing']);
$pdo = db();
$fk = $_GET['kelas'] ?? '';
$st = $pdo->prepare('SELECT m.*, s.nis, s.nama AS siswa, s.kelas, pr.nama AS perusahaan, pb.nama AS pembimbing
    FROM monitoring m JOIN siswa s ON s.id=m.siswa_id JOIN pembimbing pb ON pb.id=m.pembimbing_id
    LEFT JOIN penempatan pn ON pn.siswa_id=s.id LEFT JOIN perusahaan pr ON pr.id=pn.perusahaan_id
    WHERE (CHAR_LENGTH(?) = 0 OR s.kelas = ?) ORDER BY s.nama, m.tanggal');
$st->execute([$fk, $fk]); $rows = $st->fetchAll();
$avg = $rows ? round(array_sum(array_column($rows, 'nilai')) / count($rows), 1) : 0;
$kelas = $pdo->query('SELECT DISTINCT kelas FROM siswa ORDER BY kelas')->fetchAll(PDO::FETCH_COLUMN);
layout_start('Laporan monitoring', 'laporan', 'Dicetak pada ' . hari_ini_panjang() . '.');
laporan_tabs('monitoring');
?>
<form class="card filters" method="get">
  <div class="field"><label for="kelas">Kelas</label><select id="kelas" name="kelas"><?= opt_list(array_combine($kelas, $kelas), $fk, 'Semua kelas') ?></select></div>
  <div class="actions"><button class="btn" type="submit">Terapkan</button><button class="btn ghost" type="button" onclick="window.print()">Cetak laporan</button></div>
</form>
<section class="card">
<?php if (!$rows): ?><div class="empty">Belum ada data monitoring pada filter ini.</div><?php else: ?>
<div class="tablewrap"><table class="tbl"><thead><tr><th>No</th><th>Siswa</th><th>Perusahaan</th><th>Pembimbing</th><th>Tanggal</th><th class="num">Nilai</th><th>Catatan</th><th>Rekomendasi</th></tr></thead><tbody>
<?php foreach ($rows as $i => $r): ?>
  <tr><td><?= $i + 1 ?></td><td><?= e($r['siswa']) ?><br><span class="note"><?= e($r['nis']) ?> | <?= e($r['kelas']) ?></span></td><td><?= e($r['perusahaan'] ?: '-') ?></td><td><?= e($r['pembimbing']) ?></td><td><?= tgl($r['tanggal']) ?></td><td class="num"><strong><?= (int) $r['nilai'] ?></strong></td><td><?= e($r['catatan']) ?></td><td><?= e($r['rekomendasi'] ?: '-') ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<p class="note" style="margin-top:12px">Jumlah catatan: <?= count($rows) ?>. Rata-rata nilai: <strong><?= $avg ?></strong>.</p>
<?php endif; ?></section>
<?php layout_end();
