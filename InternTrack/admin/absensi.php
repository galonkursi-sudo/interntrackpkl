<?php
require_once __DIR__ . '/../config/helpers.php';
require_login(['admin', 'pembimbing']);
$pdo = db();
$err = []; $in = ['siswa_id' => 0, 'tanggal' => date('Y-m-d'), 'status' => '', 'keterangan' => ''];
$statusList = ['Hadir' => 'Hadir', 'Izin' => 'Izin', 'Sakit' => 'Sakit', 'Alpa' => 'Alpa'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $in = ['siswa_id' => (int) ($_POST['siswa_id'] ?? 0), 'tanggal' => $_POST['tanggal'] ?? '', 'status' => $_POST['status'] ?? '', 'keterangan' => trim($_POST['keterangan'] ?? '')];
    if (!$in['siswa_id'])  { $err['siswa_id'] = 'Siswa wajib dipilih.'; }
    if ($in['tanggal'] === '') { $err['tanggal'] = 'Tanggal wajib diisi.'; }
    if (!isset($statusList[$in['status']])) { $err['status'] = 'Status wajib dipilih.'; }
    if (!$err) {
        $c = $pdo->prepare('SELECT COUNT(*) FROM absensi WHERE siswa_id=? AND tanggal=?'); $c->execute([$in['siswa_id'], $in['tanggal']]);
        if ($c->fetchColumn() > 0) { $err['tanggal'] = 'Kehadiran siswa ini pada tanggal tersebut sudah tercatat.'; }
    }
    if (!$err) {
        $pdo->prepare('INSERT INTO absensi (siswa_id, tanggal, status, keterangan) VALUES (?,?,?,?)')->execute([$in['siswa_id'], $in['tanggal'], $in['status'], $in['keterangan'] ?: null]);
        flash('success', 'Kehadiran tersimpan.');
        redirect('admin/absensi.php');
    }
}

$fs = (int) ($_GET['siswa'] ?? 0); $fst = $_GET['status'] ?? ''; $fk = $_GET['kelas'] ?? '';
$wh = []; $pr = [];
if ($fs) { $wh[] = 's.id=?'; $pr[] = $fs; }
if ($fk !== '') { $wh[] = 's.kelas=?'; $pr[] = $fk; }
$rekap = rekap_kehadiran($wh ? 'WHERE ' . implode(' AND ', $wh) : '', $pr);

$w2 = []; $p2 = [];
if ($fs) { $w2[] = 'a.siswa_id=?'; $p2[] = $fs; }
if ($fk !== '') { $w2[] = 's.kelas=?'; $p2[] = $fk; }
if ($fst !== '') { $w2[] = 'a.status=?'; $p2[] = $fst; }
$st = $pdo->prepare('SELECT a.*, s.nama, s.kelas FROM absensi a JOIN siswa s ON s.id=a.siswa_id' . ($w2 ? ' WHERE ' . implode(' AND ', $w2) : '') . ' ORDER BY a.tanggal DESC, s.nama LIMIT 100');
$st->execute($p2); $riwayat = $st->fetchAll();
$siswaOpt = $pdo->query('SELECT id, nama FROM siswa ORDER BY nama')->fetchAll(PDO::FETCH_KEY_PAIR);
$kelas = $pdo->query('SELECT DISTINCT kelas FROM siswa ORDER BY kelas')->fetchAll(PDO::FETCH_COLUMN);
layout_start('Kehadiran siswa', 'absensi', 'Persentase kehadiran dihitung otomatis dari data absensi.');
?>
<form class="card" method="post" novalidate data-validate>
  <h2>Catat kehadiran</h2>
  <?= csrf_field() ?>
  <div class="formgrid">
    <?= select_field('siswa_id', 'Siswa', $siswaOpt, $in['siswa_id'], $err, 'Pilih siswa') ?>
    <?= field('tanggal', 'Tanggal', $in['tanggal'], $err, 'date', ['required' => 'required']) ?>
    <?= select_field('status', 'Status', $statusList, $in['status'], $err, 'Pilih status') ?>
    <?= field('keterangan', 'Keterangan', $in['keterangan'], $err, 'text', ['maxlength' => 200]) ?>
  </div>
  <button class="btn" type="submit">Simpan kehadiran</button>
</form>

<form class="card filters" method="get">
  <div class="field"><label for="siswa">Siswa</label><select id="siswa" name="siswa"><?= opt_list($siswaOpt, $fs, 'Semua siswa') ?></select></div>
  <div class="field"><label for="kelas">Kelas</label><select id="kelas" name="kelas"><?= opt_list(array_combine($kelas, $kelas), $fk, 'Semua kelas') ?></select></div>
  <div class="field"><label for="statusf">Status riwayat</label><select id="statusf" name="status"><?= opt_list($statusList, $fst, 'Semua status') ?></select></div>
  <div class="actions"><button class="btn" type="submit">Terapkan</button><a class="btn ghost" href="<?= url('admin/absensi.php') ?>">Atur ulang</a></div>
</form>

<section class="card"><h2>Rekap persentase kehadiran</h2>
<div class="tablewrap"><table class="tbl"><thead><tr><th>Nama</th><th>Kelas</th><th class="num">Hari</th><th class="num">Hadir</th><th class="num">Izin</th><th class="num">Sakit</th><th class="num">Alpa</th><th>Persentase</th><th>Status</th></tr></thead><tbody>
<?php foreach ($rekap as $r): ?>
  <tr><td><?= e($r['nama']) ?></td><td><?= e($r['kelas']) ?></td><td class="num"><?= (int) $r['total'] ?></td><td class="num"><?= (int) $r['hadir'] ?></td><td class="num"><?= (int) $r['izin'] ?></td><td class="num"><?= (int) $r['sakit'] ?></td><td class="num"><?= (int) $r['alpa'] ?></td>
  <td style="min-width:150px"><?php if ($r['total']): ?><strong><?= e($r['persen']) ?>%</strong><?= bar_persen($r['persen'], $r['status']) ?><?php else: ?>-<?php endif; ?></td>
  <td><?= $r['total'] ? badge_hadir($r['status']) : '<span class="badge b-none">Belum ada data</span>' ?></td></tr>
<?php endforeach; ?></tbody></table></div></section>

<section class="card"><h2>Riwayat kehadiran</h2>
<?php if (!$riwayat): ?><div class="empty">Belum ada data kehadiran pada filter ini.</div><?php else: ?>
<div class="tablewrap"><table class="tbl"><thead><tr><th>Tanggal</th><th>Siswa</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>
<?php foreach ($riwayat as $r): ?><tr><td><?= tgl($r['tanggal']) ?></td><td><?= e($r['nama']) ?> <span class="note"><?= e($r['kelas']) ?></span></td><td><?= badge_abs($r['status']) ?></td><td><?= e($r['keterangan'] ?: '-') ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php endif; ?></section>
<?php layout_end();
