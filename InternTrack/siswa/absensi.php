<?php
require_once __DIR__ . '/../config/helpers.php';
require_login(['siswa']);
$pdo = db();
$me = siswa_saya();
$err = []; $in = ['tanggal' => date('Y-m-d'), 'status' => '', 'keterangan' => ''];
$statusList = ['Hadir' => 'Hadir', 'Izin' => 'Izin', 'Sakit' => 'Sakit', 'Alpa' => 'Alpa'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $in = ['tanggal' => $_POST['tanggal'] ?? '', 'status' => $_POST['status'] ?? '', 'keterangan' => trim($_POST['keterangan'] ?? '')];
    if ($in['tanggal'] === '') { $err['tanggal'] = 'Tanggal wajib diisi.'; }
    elseif ($in['tanggal'] > date('Y-m-d')) { $err['tanggal'] = 'Tanggal kehadiran tidak boleh lebih dari hari ini.'; }
    if (!isset($statusList[$in['status']])) { $err['status'] = 'Status wajib dipilih.'; }
    if (!$err) {
        $c = $pdo->prepare('SELECT COUNT(*) FROM absensi WHERE siswa_id=? AND tanggal=?'); $c->execute([$me['id'], $in['tanggal']]);
        if ($c->fetchColumn() > 0) { $err['tanggal'] = 'Kehadiran pada tanggal ini sudah tercatat.'; }
    }
    if (!$err) {
        $pdo->prepare('INSERT INTO absensi (siswa_id, tanggal, status, keterangan) VALUES (?,?,?,?)')->execute([$me['id'], $in['tanggal'], $in['status'], $in['keterangan'] ?: null]);
        flash('success', 'Kehadiran tersimpan.');
        redirect('siswa/absensi.php');
    }
}
$rk = rekap_kehadiran('WHERE s.id = ?', [$me['id']])[0];
$st = $pdo->prepare('SELECT * FROM absensi WHERE siswa_id=? ORDER BY tanggal DESC'); $st->execute([$me['id']]); $rows = $st->fetchAll();
layout_start('Kehadiran', 'absensi', 'Persentase kehadiran dan statusnya dihitung otomatis oleh sistem.');
?>
<div class="grid2">
<form class="card" method="post" novalidate data-validate>
  <h2>Isi kehadiran</h2>
  <?= csrf_field() ?>
  <?= field('tanggal', 'Tanggal', $in['tanggal'], $err, 'date', ['required' => 'required', 'max' => date('Y-m-d')]) ?>
  <?= select_field('status', 'Status kehadiran', $statusList, $in['status'], $err, 'Pilih status') ?>
  <?= field('keterangan', 'Keterangan', $in['keterangan'], $err, 'text', ['maxlength' => 200, 'placeholder' => 'isi jika izin atau sakit']) ?>
  <button class="btn" type="submit">Simpan kehadiran</button>
</form>
<section class="card"><h2>Rekap kehadiranmu</h2>
  <?php if (!$rk['total']): ?><div class="empty">Belum ada data. Catat kehadiran pertamamu.</div><?php else: ?>
  <p class="big"><?= e($rk['persen']) ?>%</p>
  <?= bar_persen($rk['persen'], $rk['status']) ?>
  <p style="margin:12px 0"><?= badge_hadir($rk['status']) ?></p>
  <p class="note"><?= $rk['hadir'] ?> hadir dari <?= $rk['total'] ?> hari (<?= $rk['hadir'] ?> / <?= $rk['total'] ?> x 100 = <?= e($rk['persen']) ?>%). Izin <?= $rk['izin'] ?>, sakit <?= $rk['sakit'] ?>, alpa <?= $rk['alpa'] ?>.</p><?php endif; ?></section>
</div>
<section class="card"><h2>Riwayat kehadiran</h2>
<?php if (!$rows): ?><div class="empty">Belum ada riwayat.</div><?php else: ?>
<div class="tablewrap"><table class="tbl"><thead><tr><th>Tanggal</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= tgl($r['tanggal']) ?></td><td><?= badge_abs($r['status']) ?></td><td><?= e($r['keterangan'] ?: '-') ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php endif; ?></section>
<?php layout_end();
