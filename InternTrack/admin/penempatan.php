<?php
require_once __DIR__ . '/../config/helpers.php';
require_login(['admin', 'pembimbing']);
$pdo = db();
$aksi = $_GET['aksi'] ?? 'list';
$err = []; $row = [];
$statusList = ['Belum Mulai' => 'Belum Mulai', 'Aktif' => 'Aktif', 'Selesai' => 'Selesai'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $op = $_POST['op'] ?? '';
    if ($op === 'hapus') {
        $pdo->prepare('DELETE FROM penempatan WHERE id=?')->execute([(int) $_POST['id']]);
        flash('success', 'Penempatan dihapus. Siswa kembali berstatus belum ditempatkan.');
        redirect('admin/penempatan.php');
    }
    if ($op === 'status') {  // ubah status PKL langsung dari daftar
        $s = $_POST['status'] ?? '';
        if (isset($statusList[$s])) {
            $pdo->prepare('UPDATE penempatan SET status=? WHERE id=?')->execute([$s, (int) $_POST['id']]);
            flash('success', 'Status PKL diubah menjadi ' . $s . '.');
        }
        redirect('admin/penempatan.php');
    }
    if ($op === 'simpan') {
        $id = (int) ($_POST['id'] ?? 0);
        $row = ['id' => $id, 'siswa_id' => (int) ($_POST['siswa_id'] ?? 0), 'perusahaan_id' => (int) ($_POST['perusahaan_id'] ?? 0), 'pembimbing_id' => (int) ($_POST['pembimbing_id'] ?? 0),
                'tanggal_mulai' => $_POST['tanggal_mulai'] ?? '', 'tanggal_selesai' => $_POST['tanggal_selesai'] ?? '', 'status' => $_POST['status'] ?? ''];
        if (!$row['siswa_id'])      { $err['siswa_id'] = 'Siswa wajib dipilih.'; }
        if (!$row['perusahaan_id']) { $err['perusahaan_id'] = 'Perusahaan wajib dipilih.'; }
        if (!$row['pembimbing_id']) { $err['pembimbing_id'] = 'Pembimbing wajib dipilih.'; }
        if ($row['tanggal_mulai'] === '')   { $err['tanggal_mulai'] = 'Tanggal mulai wajib diisi.'; }
        if ($row['tanggal_selesai'] === '') { $err['tanggal_selesai'] = 'Tanggal selesai wajib diisi.'; }
        if (!isset($statusList[$row['status']])) { $err['status'] = 'Status wajib dipilih.'; }
        if (!$err && $row['tanggal_selesai'] < $row['tanggal_mulai']) { $err['tanggal_selesai'] = 'Tanggal selesai tidak boleh sebelum tanggal mulai.'; }
        if (!isset($err['siswa_id'])) {
            $c = $pdo->prepare('SELECT COUNT(*) FROM penempatan WHERE siswa_id=? AND id<>?'); $c->execute([$row['siswa_id'], $id]);
            if ($c->fetchColumn() > 0) { $err['siswa_id'] = 'Siswa ini sudah memiliki penempatan.'; }
        }
        if (!$err) {
            $v = [$row['siswa_id'], $row['perusahaan_id'], $row['pembimbing_id'], $row['tanggal_mulai'], $row['tanggal_selesai'], $row['status']];
            if ($id) { $pdo->prepare('UPDATE penempatan SET siswa_id=?, perusahaan_id=?, pembimbing_id=?, tanggal_mulai=?, tanggal_selesai=?, status=? WHERE id=?')->execute(array_merge($v, [$id])); }
            else { $pdo->prepare('INSERT INTO penempatan (siswa_id, perusahaan_id, pembimbing_id, tanggal_mulai, tanggal_selesai, status) VALUES (?,?,?,?,?,?)')->execute($v); }
            flash('success', $id ? 'Penempatan diperbarui.' : 'Penempatan tersimpan.');
            redirect('admin/penempatan.php');
        }
        $aksi = 'form';
    }
}

if ($aksi === 'form') {
    $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    if (!$row) {
        $row = ['id' => 0, 'siswa_id' => (int) ($_GET['siswa'] ?? 0), 'perusahaan_id' => 0, 'pembimbing_id' => 0, 'tanggal_mulai' => '', 'tanggal_selesai' => '', 'status' => 'Belum Mulai'];
        if ($id) { $st = $pdo->prepare('SELECT * FROM penempatan WHERE id=?'); $st->execute([$id]); $row = $st->fetch() ?: $row; }
    }
    $sw = $pdo->prepare("SELECT id, CONCAT(nama, ' (', nis, ')') FROM siswa WHERE id NOT IN (SELECT siswa_id FROM penempatan) OR id = ? ORDER BY nama");
    $sw->execute([$row['siswa_id']]);
    $siswaOpt = $sw->fetchAll(PDO::FETCH_KEY_PAIR);
    $persOpt = $pdo->query('SELECT id, nama FROM perusahaan ORDER BY nama')->fetchAll(PDO::FETCH_KEY_PAIR);
    $pembOpt = $pdo->query('SELECT id, nama FROM pembimbing ORDER BY nama')->fetchAll(PDO::FETCH_KEY_PAIR);
    layout_start($row['id'] ? 'Edit penempatan' : 'Tempatkan siswa', 'penempatan', 'Siswa A ke Perusahaan X dengan Pembimbing Y.');
    ?>
    <form class="card" method="post" action="<?= url('admin/penempatan.php') ?>" novalidate data-validate style="max-width:760px">
      <?= csrf_field() ?><input type="hidden" name="op" value="simpan"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
      <div class="formgrid">
        <div class="full"><?= select_field('siswa_id', 'Siswa', $siswaOpt, $row['siswa_id'], $err, 'Pilih siswa') ?></div>
        <?= select_field('perusahaan_id', 'Perusahaan', $persOpt, $row['perusahaan_id'], $err, 'Pilih perusahaan') ?>
        <?= select_field('pembimbing_id', 'Guru pembimbing', $pembOpt, $row['pembimbing_id'], $err, 'Pilih pembimbing') ?>
        <?= field('tanggal_mulai', 'Tanggal mulai', $row['tanggal_mulai'], $err, 'date', ['required' => 'required']) ?>
        <?= field('tanggal_selesai', 'Tanggal selesai', $row['tanggal_selesai'], $err, 'date', ['required' => 'required']) ?>
        <?= select_field('status', 'Status PKL', $statusList, $row['status'], $err, 'Pilih status') ?>
      </div>
      <div class="actions"><button class="btn" type="submit">Simpan penempatan</button><a class="btn ghost" href="<?= url('admin/penempatan.php') ?>">Batal</a></div>
    </form>
    <?php
    layout_end(); exit;
}

$q = trim($_GET['q'] ?? ''); $fs = $_GET['status'] ?? '';
$w = ['(s.nama LIKE ? OR pr.nama LIKE ? OR pb.nama LIKE ?)']; $p = ["%$q%", "%$q%", "%$q%"];
if ($fs !== '') { $w[] = 'pn.status=?'; $p[] = $fs; }
$st = $pdo->prepare('SELECT pn.*, s.nama AS siswa, s.kelas, pr.nama AS perusahaan, pb.nama AS pembimbing FROM penempatan pn
    JOIN siswa s ON s.id=pn.siswa_id JOIN perusahaan pr ON pr.id=pn.perusahaan_id JOIN pembimbing pb ON pb.id=pn.pembimbing_id
    WHERE ' . implode(' AND ', $w) . ' ORDER BY s.nama');
$st->execute($p); $rows = $st->fetchAll();
$belumTempat = (int) $pdo->query('SELECT COUNT(*) FROM siswa WHERE id NOT IN (SELECT siswa_id FROM penempatan)')->fetchColumn();
layout_start('Penempatan PKL', 'penempatan', count($rows) . ' penempatan. ' . $belumTempat . ' siswa belum ditempatkan.');
?>
<form class="card filters" method="get">
  <div class="field grow"><label for="q">Cari siswa, perusahaan, atau pembimbing</label><input id="q" name="q" value="<?= e($q) ?>"></div>
  <div class="field"><label for="status">Status</label><select id="status" name="status"><?= opt_list($statusList, $fs, 'Semua status') ?></select></div>
  <div class="actions"><button class="btn" type="submit">Terapkan</button><a class="btn ghost" href="<?= url('admin/penempatan.php') ?>">Atur ulang</a></div>
</form>
<div class="toolbar"><span></span><a class="btn" href="<?= url('admin/penempatan.php?aksi=form') ?>">Tempatkan siswa</a></div>
<div class="card">
<?php if (!$rows): ?><div class="empty">Belum ada penempatan yang cocok.</div><?php else: ?>
<div class="tablewrap"><table class="tbl"><thead><tr><th>Siswa</th><th>Perusahaan</th><th>Pembimbing</th><th>Periode</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
  <tr><td><strong><?= e($r['siswa']) ?></strong><br><span class="note"><?= e($r['kelas']) ?></span></td><td><?= e($r['perusahaan']) ?></td><td><?= e($r['pembimbing']) ?></td>
  <td><?= tgl($r['tanggal_mulai']) ?><br><span class="note">sampai <?= tgl($r['tanggal_selesai']) ?></span></td>
  <td><form method="post"><?= csrf_field() ?><input type="hidden" name="op" value="status"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
      <select name="status" data-autosubmit aria-label="Ubah status PKL <?= e($r['siswa']) ?>" style="padding:4px 8px"><?= opt_list($statusList, $r['status']) ?></select></form></td>
  <td><div class="actions"><a class="btn sm ghost" href="<?= url('admin/penempatan.php?aksi=form&id=' . $r['id']) ?>">Edit</a>
  <form method="post" data-confirm="Hapus penempatan <?= e($r['siswa']) ?>?"><?= csrf_field() ?><input type="hidden" name="op" value="hapus"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn sm danger" type="submit">Hapus</button></form></div></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php endif; ?></div>
<?php layout_end();
