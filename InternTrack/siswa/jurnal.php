<?php
// ALGORITMA 2 - Input jurnal kegiatan: cek tanggal -> cek kegiatan -> simpan ke database -> tampilkan di riwayat
require_once __DIR__ . '/../config/helpers.php';
require_login(['siswa']);
$pdo = db();
$me = siswa_saya();
$err = []; $in = ['tanggal' => date('Y-m-d'), 'kegiatan' => '', 'hasil' => '', 'kendala' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['op'] ?? '') === 'hapus') {
        $pdo->prepare('DELETE FROM jurnal WHERE id=? AND siswa_id=?')->execute([(int) $_POST['id'], $me['id']]);
        flash('success', 'Jurnal dihapus.');
        redirect('siswa/jurnal.php');
    }
    $in = ['tanggal' => $_POST['tanggal'] ?? '', 'kegiatan' => trim($_POST['kegiatan'] ?? ''), 'hasil' => trim($_POST['hasil'] ?? ''), 'kendala' => trim($_POST['kendala'] ?? '')];
    if ($in['tanggal'] === '')  { $err['tanggal'] = 'Tanggal wajib diisi.'; }
    elseif ($in['tanggal'] > date('Y-m-d')) { $err['tanggal'] = 'Tanggal jurnal tidak boleh lebih dari hari ini.'; }
    if ($in['kegiatan'] === '') { $err['kegiatan'] = 'Kegiatan wajib diisi.'; }
    if (!$err) {
        $pdo->prepare('INSERT INTO jurnal (siswa_id, tanggal, kegiatan, hasil, kendala) VALUES (?,?,?,?,?)')
            ->execute([$me['id'], $in['tanggal'], $in['kegiatan'], $in['hasil'] ?: null, $in['kendala'] ?: null]);
        flash('success', 'Jurnal tersimpan. Guru pembimbing dapat melihatnya.');
        redirect('siswa/jurnal.php');
    }
}
$st = $pdo->prepare('SELECT * FROM jurnal WHERE siswa_id=? ORDER BY tanggal DESC, id DESC'); $st->execute([$me['id']]); $rows = $st->fetchAll();
layout_start('Jurnal kegiatan', 'jurnal', 'Tulis apa yang kamu kerjakan, hasilnya, dan kendala yang dihadapi.');
?>
<div class="grid2" style="grid-template-columns:1fr 1.2fr">
<form class="card" method="post" novalidate data-validate>
  <h2>Tulis jurnal hari ini</h2>
  <?= csrf_field() ?>
  <?= field('tanggal', 'Tanggal', $in['tanggal'], $err, 'date', ['required' => 'required', 'max' => date('Y-m-d')]) ?>
  <?= textarea_field('kegiatan', 'Kegiatan', $in['kegiatan'], $err, true, 3) ?>
  <?= textarea_field('hasil', 'Hasil', $in['hasil'], $err, false, 2) ?>
  <?= textarea_field('kendala', 'Kendala', $in['kendala'], $err, false, 2) ?>
  <button class="btn" type="submit">Simpan jurnal</button>
</form>
<section class="card"><h2>Riwayat jurnal <span class="note">(<?= count($rows) ?>)</span></h2>
  <?php if (!$rows): ?><div class="empty">Belum ada jurnal. Isi formulir di samping untuk memulai.</div><?php endif;
  foreach ($rows as $r): ?>
  <div class="entry"><div class="meta"><?= tgl($r['tanggal']) ?></div><strong><?= e($r['kegiatan']) ?></strong>
    <div class="note">Hasil: <?= e($r['hasil'] ?: '-') ?><br>Kendala: <?= e($r['kendala'] ?: '-') ?></div>
    <form method="post" data-confirm="Hapus jurnal tanggal <?= tgl($r['tanggal']) ?>?" style="margin-top:6px"><?= csrf_field() ?><input type="hidden" name="op" value="hapus"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn sm danger" type="submit">Hapus</button></form></div>
  <?php endforeach; ?></section>
</div>
<?php layout_end();
