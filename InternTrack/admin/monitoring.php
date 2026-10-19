<?php
require_once __DIR__ . '/../config/helpers.php';
require_login(['admin', 'pembimbing']);
$pdo = db();
$aksi = $_GET['aksi'] ?? 'list';
$err = []; $row = [];
$saya = user()['role'] === 'pembimbing' ? pembimbing_saya() : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $op = $_POST['op'] ?? '';
    if ($op === 'hapus') {
        $pdo->prepare('DELETE FROM monitoring WHERE id=?')->execute([(int) $_POST['id']]);
        flash('success', 'Catatan monitoring dihapus.');
        redirect('admin/monitoring.php');
    }
    if ($op === 'simpan') {
        $id = (int) ($_POST['id'] ?? 0);
        $row = ['id' => $id, 'siswa_id' => (int) ($_POST['siswa_id'] ?? 0), 'pembimbing_id' => (int) ($_POST['pembimbing_id'] ?? 0), 'tanggal' => $_POST['tanggal'] ?? '',
                'catatan' => trim($_POST['catatan'] ?? ''), 'nilai' => trim($_POST['nilai'] ?? ''), 'rekomendasi' => trim($_POST['rekomendasi'] ?? '')];
        if (!$row['siswa_id'])      { $err['siswa_id'] = 'Siswa wajib dipilih.'; }
        if (!$row['pembimbing_id']) { $err['pembimbing_id'] = 'Pembimbing wajib dipilih.'; }
        if ($row['tanggal'] === '') { $err['tanggal'] = 'Tanggal monitoring wajib diisi.'; }
        if ($row['catatan'] === '') { $err['catatan'] = 'Catatan wajib diisi.'; }
        if ($row['nilai'] === '' || !ctype_digit($row['nilai']) || (int) $row['nilai'] > 100) { $err['nilai'] = 'Nilai harus berupa angka 0 sampai 100.'; }
        if (!$err) {
            $v = [$row['siswa_id'], $row['pembimbing_id'], $row['tanggal'], $row['catatan'], (int) $row['nilai'], $row['rekomendasi'] ?: null];
            if ($id) { $pdo->prepare('UPDATE monitoring SET siswa_id=?, pembimbing_id=?, tanggal=?, catatan=?, nilai=?, rekomendasi=? WHERE id=?')->execute(array_merge($v, [$id])); }
            else { $pdo->prepare('INSERT INTO monitoring (siswa_id, pembimbing_id, tanggal, catatan, nilai, rekomendasi) VALUES (?,?,?,?,?,?)')->execute($v); }
            flash('success', 'Hasil monitoring tersimpan.');
            redirect('admin/monitoring.php');
        }
        $aksi = 'form';
    }
}

if ($aksi === 'form') {
    if (!$row) {
        $id = (int) ($_GET['id'] ?? 0);
        $row = ['id' => 0, 'siswa_id' => 0, 'pembimbing_id' => (int) $saya, 'tanggal' => date('Y-m-d'), 'catatan' => '', 'nilai' => '', 'rekomendasi' => ''];
        if ($id) { $st = $pdo->prepare('SELECT * FROM monitoring WHERE id=?'); $st->execute([$id]); $row = $st->fetch() ?: $row; }
    }
    $siswaOpt = $pdo->query("SELECT s.id, CONCAT(s.nama, ' - ', pr.nama) FROM penempatan pn JOIN siswa s ON s.id=pn.siswa_id JOIN perusahaan pr ON pr.id=pn.perusahaan_id ORDER BY s.nama")->fetchAll(PDO::FETCH_KEY_PAIR);
    $pembOpt = $pdo->query('SELECT id, nama FROM pembimbing ORDER BY nama')->fetchAll(PDO::FETCH_KEY_PAIR);
    layout_start($row['id'] ? 'Edit monitoring' : 'Catat monitoring', 'monitoring');
    ?>
    <form class="card" method="post" action="<?= url('admin/monitoring.php') ?>" novalidate data-validate style="max-width:760px">
      <?= csrf_field() ?><input type="hidden" name="op" value="simpan"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
      <div class="formgrid">
        <?= select_field('siswa_id', 'Nama siswa', $siswaOpt, $row['siswa_id'], $err, 'Pilih siswa') ?>
        <?= select_field('pembimbing_id', 'Guru pembimbing', $pembOpt, $row['pembimbing_id'], $err, 'Pilih pembimbing') ?>
        <?= field('tanggal', 'Tanggal monitoring', $row['tanggal'], $err, 'date', ['required' => 'required']) ?>
        <?= field('nilai', 'Nilai (0 sampai 100)', $row['nilai'], $err, 'number', ['required' => 'required', 'min' => 0, 'max' => 100]) ?>
        <div class="full"><?= textarea_field('catatan', 'Catatan', $row['catatan'], $err, true, 4) ?></div>
        <div class="full"><?= textarea_field('rekomendasi', 'Rekomendasi', $row['rekomendasi'], $err, false, 3) ?></div>
      </div>
      <div class="actions"><button class="btn" type="submit">Simpan monitoring</button><a class="btn ghost" href="<?= url('admin/monitoring.php') ?>">Batal</a></div>
    </form>
    <?php
    layout_end(); exit;
}

$q = trim($_GET['q'] ?? '');
$st = $pdo->prepare('SELECT m.*, s.nama AS siswa, s.kelas, pb.nama AS pembimbing FROM monitoring m JOIN siswa s ON s.id=m.siswa_id JOIN pembimbing pb ON pb.id=m.pembimbing_id
    WHERE s.nama LIKE ? OR pb.nama LIKE ? ORDER BY m.tanggal DESC, m.id DESC');
$st->execute(["%$q%", "%$q%"]); $rows = $st->fetchAll();
layout_start('Monitoring pembimbing', 'monitoring', 'Catatan, nilai, dan rekomendasi dari guru pembimbing.');
?>
<form class="card filters" method="get">
  <div class="field grow"><label for="q">Cari nama siswa atau pembimbing</label><input id="q" name="q" value="<?= e($q) ?>"></div>
  <div class="actions"><button class="btn" type="submit">Cari</button><a class="btn ghost" href="<?= url('admin/monitoring.php') ?>">Atur ulang</a></div>
</form>
<div class="toolbar"><span></span><a class="btn" href="<?= url('admin/monitoring.php?aksi=form') ?>">Catat monitoring</a></div>
<div class="card">
<?php if (!$rows): ?><div class="empty">Belum ada catatan monitoring yang cocok.</div><?php else: ?>
<div class="tablewrap"><table class="tbl"><thead><tr><th>Tanggal</th><th>Siswa</th><th>Pembimbing</th><th>Catatan</th><th class="num">Nilai</th><th>Rekomendasi</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
  <tr><td><?= tgl($r['tanggal']) ?></td><td><?= e($r['siswa']) ?><br><span class="note"><?= e($r['kelas']) ?></span></td><td><?= e($r['pembimbing']) ?></td><td><?= e($r['catatan']) ?></td><td class="num"><strong><?= (int) $r['nilai'] ?></strong></td><td><?= e($r['rekomendasi'] ?: '-') ?></td>
  <td><div class="actions"><a class="btn sm ghost" href="<?= url('admin/monitoring.php?aksi=form&id=' . $r['id']) ?>">Edit</a>
  <form method="post" data-confirm="Hapus catatan monitoring <?= e($r['siswa']) ?>?"><?= csrf_field() ?><input type="hidden" name="op" value="hapus"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn sm danger" type="submit">Hapus</button></form></div></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php endif; ?></div>
<?php layout_end();
