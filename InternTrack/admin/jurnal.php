<?php
require_once __DIR__ . '/../config/helpers.php';
require_login(['admin', 'pembimbing']);
$pdo = db();
$fs = (int) ($_GET['siswa'] ?? 0); $ft = $_GET['tanggal'] ?? ''; $q = trim($_GET['q'] ?? '');
$w = ['(j.kegiatan LIKE ? OR j.hasil LIKE ?)']; $p = ["%$q%", "%$q%"];
if ($fs) { $w[] = 'j.siswa_id=?'; $p[] = $fs; }
if ($ft !== '') { $w[] = 'j.tanggal=?'; $p[] = $ft; }
$st = $pdo->prepare('SELECT j.*, s.nama, s.kelas FROM jurnal j JOIN siswa s ON s.id=j.siswa_id WHERE ' . implode(' AND ', $w) . ' ORDER BY j.tanggal DESC, j.id DESC LIMIT 200');
$st->execute($p); $rows = $st->fetchAll();
$siswaOpt = $pdo->query('SELECT id, nama FROM siswa ORDER BY nama')->fetchAll(PDO::FETCH_KEY_PAIR);
layout_start('Aktivitas siswa', 'jurnal', 'Jurnal kegiatan harian yang ditulis siswa. ' . count($rows) . ' entri ditampilkan.');
?>
<form class="card filters" method="get">
  <div class="field"><label for="siswa">Siswa</label><select id="siswa" name="siswa"><?= opt_list($siswaOpt, $fs, 'Semua siswa') ?></select></div>
  <div class="field"><label for="tanggal">Tanggal</label><input id="tanggal" type="date" name="tanggal" value="<?= e($ft) ?>"></div>
  <div class="field grow"><label for="q">Cari isi kegiatan</label><input id="q" name="q" value="<?= e($q) ?>"></div>
  <div class="actions"><button class="btn" type="submit">Terapkan</button><a class="btn ghost" href="<?= url('admin/jurnal.php') ?>">Atur ulang</a></div>
</form>
<div class="card">
<?php if (!$rows): ?><div class="empty">Tidak ada jurnal pada filter ini.</div><?php else: ?>
<div class="tablewrap"><table class="tbl"><thead><tr><th>Tanggal</th><th>Siswa</th><th>Kegiatan</th><th>Hasil</th><th>Kendala</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
  <tr><td><?= tgl($r['tanggal']) ?></td><td><?= e($r['nama']) ?><br><span class="note"><?= e($r['kelas']) ?></span></td><td><?= e($r['kegiatan']) ?></td><td><?= e($r['hasil'] ?: '-') ?></td><td><?= e($r['kendala'] ?: '-') ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php endif; ?></div>
<?php layout_end();
