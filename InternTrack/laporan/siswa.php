<?php
// Laporan 1: Data siswa PKL
require_once __DIR__ . '/../config/helpers.php';
require_login(['admin', 'pembimbing']);
$pdo = db();
$f = ['kelas' => $_GET['kelas'] ?? '', 'perusahaan' => (int) ($_GET['perusahaan'] ?? 0), 'status' => $_GET['status'] ?? '', 'pembimbing' => (int) ($_GET['pembimbing'] ?? 0)];
$rows = query_siswa_pkl($f);
$kelas = $pdo->query('SELECT DISTINCT kelas FROM siswa ORDER BY kelas')->fetchAll(PDO::FETCH_COLUMN);
$pers = $pdo->query('SELECT id, nama FROM perusahaan ORDER BY nama')->fetchAll(PDO::FETCH_KEY_PAIR);
$pemb = $pdo->query('SELECT id, nama FROM pembimbing ORDER BY nama')->fetchAll(PDO::FETCH_KEY_PAIR);
layout_start('Laporan data siswa PKL', 'laporan', 'Dicetak pada ' . hari_ini_panjang() . '.');
laporan_tabs('siswa');
?>
<form class="card filters" method="get">
  <div class="field"><label for="kelas">Kelas</label><select id="kelas" name="kelas"><?= opt_list(array_combine($kelas, $kelas), $f['kelas'], 'Semua kelas') ?></select></div>
  <div class="field"><label for="perusahaan">Perusahaan</label><select id="perusahaan" name="perusahaan"><?= opt_list($pers, $f['perusahaan'], 'Semua perusahaan') ?></select></div>
  <div class="field"><label for="status">Status PKL</label><select id="status" name="status"><?= opt_list(['Belum Mulai' => 'Belum Mulai', 'Aktif' => 'Aktif', 'Selesai' => 'Selesai', 'Belum Ditempatkan' => 'Belum ditempatkan'], $f['status'], 'Semua status') ?></select></div>
  <div class="field"><label for="pembimbing">Pembimbing</label><select id="pembimbing" name="pembimbing"><?= opt_list($pemb, $f['pembimbing'], 'Semua pembimbing') ?></select></div>
  <div class="actions"><button class="btn" type="submit">Terapkan</button><button class="btn ghost" type="button" onclick="window.print()">Cetak laporan</button></div>
</form>
<section class="card">
<?php if (!$rows): ?><div class="empty">Tidak ada data pada filter ini.</div><?php else: ?>
<div class="tablewrap"><table class="tbl"><thead><tr><th>No</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Perusahaan</th><th>Pembimbing</th><th>Periode</th><th>Status</th></tr></thead><tbody>
<?php foreach ($rows as $i => $r): ?>
  <tr><td><?= $i + 1 ?></td><td><?= e($r['nis']) ?></td><td><?= e($r['nama']) ?></td><td><?= e($r['kelas']) ?></td><td><?= e($r['perusahaan'] ?: '-') ?></td><td><?= e($r['pembimbing'] ?: '-') ?></td>
  <td><?= $r['status'] ? tgl($r['tanggal_mulai']) . ' - ' . tgl($r['tanggal_selesai']) : '-' ?></td><td><?= badge_pkl($r['status']) ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<p class="note" style="margin-top:12px">Total: <?= count($rows) ?> siswa.</p>
<?php endif; ?></section>
<?php layout_end();
