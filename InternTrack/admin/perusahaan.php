<?php
require_once __DIR__ . '/../config/helpers.php';
require_login(['admin', 'pembimbing']);
$pdo = db();
$aksi = $_GET['aksi'] ?? 'list';
$err = []; $row = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $op = $_POST['op'] ?? '';
    if ($op === 'hapus') {
        try {
            $pdo->prepare('DELETE FROM perusahaan WHERE id = ?')->execute([(int) $_POST['id']]);
            flash('success', 'Data perusahaan dihapus.');
        } catch (PDOException $ex) {
            flash('error', 'Perusahaan tidak bisa dihapus karena masih dipakai pada data penempatan siswa.');
        }
        redirect('admin/perusahaan.php');
    }
    if ($op === 'simpan') {
        $id = (int) ($_POST['id'] ?? 0);
        $row = ['id' => $id, 'nama' => trim($_POST['nama'] ?? ''), 'alamat' => trim($_POST['alamat'] ?? ''), 'bidang' => trim($_POST['bidang'] ?? ''), 'kontak' => trim($_POST['kontak'] ?? '')];
        if ($row['nama'] === '')   { $err['nama'] = 'Nama perusahaan tidak boleh kosong.'; }
        if ($row['alamat'] === '') { $err['alamat'] = 'Alamat wajib diisi.'; }
        if ($row['bidang'] === '') { $err['bidang'] = 'Bidang usaha wajib diisi.'; }
        if ($row['kontak'] === '') { $err['kontak'] = 'Kontak wajib diisi.'; }
        if (!$err) {
            if ($id) {
                $pdo->prepare('UPDATE perusahaan SET nama=?, alamat=?, bidang=?, kontak=? WHERE id=?')->execute([$row['nama'], $row['alamat'], $row['bidang'], $row['kontak'], $id]);
            } else {
                $pdo->prepare('INSERT INTO perusahaan (nama, alamat, bidang, kontak) VALUES (?,?,?,?)')->execute([$row['nama'], $row['alamat'], $row['bidang'], $row['kontak']]);
            }
            flash('success', $id ? 'Data perusahaan diperbarui.' : 'Perusahaan baru tersimpan.');
            redirect('admin/perusahaan.php');
        }
        $aksi = 'form';
    }
}

if ($aksi === 'form') {
    if (!$row) {
        $id = (int) ($_GET['id'] ?? 0);
        $row = ['id' => 0, 'nama' => '', 'alamat' => '', 'bidang' => '', 'kontak' => ''];
        if ($id) { $st = $pdo->prepare('SELECT * FROM perusahaan WHERE id=?'); $st->execute([$id]); $row = $st->fetch() ?: $row; }
    }
    layout_start($row['id'] ? 'Edit perusahaan' : 'Tambah perusahaan', 'perusahaan');
    ?>
    <form class="card" method="post" action="<?= url('admin/perusahaan.php') ?>" novalidate data-validate style="max-width:760px">
      <?= csrf_field() ?><input type="hidden" name="op" value="simpan"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
      <div class="formgrid">
        <div class="full"><?= field('nama', 'Nama perusahaan atau instansi', $row['nama'], $err, 'text', ['required' => 'required']) ?></div>
        <div class="full"><?= textarea_field('alamat', 'Alamat', $row['alamat'], $err, true) ?></div>
        <?= field('bidang', 'Bidang usaha', $row['bidang'], $err, 'text', ['required' => 'required', 'placeholder' => 'contoh: Software House']) ?>
        <?= field('kontak', 'Kontak (telepon atau email)', $row['kontak'], $err, 'text', ['required' => 'required']) ?>
      </div>
      <div class="actions"><button class="btn" type="submit">Simpan perusahaan</button><a class="btn ghost" href="<?= url('admin/perusahaan.php') ?>">Batal</a></div>
    </form>
    <?php
    layout_end(); exit;
}

if ($aksi === 'detail') {
    $id = (int) ($_GET['id'] ?? 0);
    $st = $pdo->prepare('SELECT * FROM perusahaan WHERE id=?'); $st->execute([$id]); $p = $st->fetch();
    if (!$p) { flash('error', 'Data perusahaan tidak ditemukan.'); redirect('admin/perusahaan.php'); }
    $sw = $pdo->prepare('SELECT s.nis, s.nama, s.kelas, pn.status, pb.nama AS pembimbing FROM penempatan pn JOIN siswa s ON s.id=pn.siswa_id JOIN pembimbing pb ON pb.id=pn.pembimbing_id WHERE pn.perusahaan_id=? ORDER BY s.nama');
    $sw->execute([$id]); $sw = $sw->fetchAll();
    layout_start('Detail perusahaan', 'perusahaan', $p['nama']);
    ?>
    <section class="card"><dl class="kv"><dt>Nama</dt><dd><?= e($p['nama']) ?></dd><dt>Alamat</dt><dd><?= e($p['alamat']) ?></dd><dt>Bidang</dt><dd><?= e($p['bidang']) ?></dd><dt>Kontak</dt><dd><?= e($p['kontak']) ?></dd></dl></section>
    <section class="card"><h2>Siswa yang ditempatkan</h2>
    <?php if (!$sw): ?><div class="empty">Belum ada siswa di perusahaan ini.</div><?php else: ?>
      <div class="tablewrap"><table class="tbl"><thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Pembimbing</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($sw as $r): ?><tr><td><?= e($r['nis']) ?></td><td><?= e($r['nama']) ?></td><td><?= e($r['kelas']) ?></td><td><?= e($r['pembimbing']) ?></td><td><?= badge_pkl($r['status']) ?></td></tr><?php endforeach; ?></tbody></table></div>
    <?php endif; ?></section>
    <div class="actions"><a class="btn" href="<?= url('admin/perusahaan.php?aksi=form&id=' . $id) ?>">Edit perusahaan</a><a class="btn ghost" href="<?= url('admin/perusahaan.php') ?>">Kembali ke daftar</a></div>
    <?php
    layout_end(); exit;
}

$q = trim($_GET['q'] ?? '');
$st = $pdo->prepare("SELECT p.*, (SELECT COUNT(*) FROM penempatan pn WHERE pn.perusahaan_id=p.id) AS jml FROM perusahaan p WHERE p.nama LIKE ? OR p.bidang LIKE ? OR p.alamat LIKE ? ORDER BY p.nama");
$st->execute(["%$q%", "%$q%", "%$q%"]); $rows = $st->fetchAll();
layout_start('Data perusahaan', 'perusahaan', count($rows) . ' perusahaan atau instansi.');
?>
<form class="card filters" method="get">
  <div class="field grow"><label for="q">Cari nama, bidang, atau alamat</label><input id="q" name="q" value="<?= e($q) ?>"></div>
  <div class="actions"><button class="btn" type="submit">Cari</button><a class="btn ghost" href="<?= url('admin/perusahaan.php') ?>">Atur ulang</a></div>
</form>
<div class="toolbar"><span></span><a class="btn" href="<?= url('admin/perusahaan.php?aksi=form') ?>">Tambah perusahaan</a></div>
<div class="card">
<?php if (!$rows): ?><div class="empty">Tidak ada perusahaan yang cocok dengan pencarian.</div><?php else: ?>
<div class="tablewrap"><table class="tbl"><thead><tr><th>Nama</th><th>Bidang</th><th>Kontak</th><th class="num">Siswa</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
  <tr><td><strong><?= e($r['nama']) ?></strong><br><span class="note"><?= e($r['alamat']) ?></span></td><td><?= e($r['bidang']) ?></td><td><?= e($r['kontak']) ?></td><td class="num"><?= (int) $r['jml'] ?></td>
  <td><div class="actions"><a class="btn sm ghost" href="<?= url('admin/perusahaan.php?aksi=detail&id=' . $r['id']) ?>">Detail</a><a class="btn sm ghost" href="<?= url('admin/perusahaan.php?aksi=form&id=' . $r['id']) ?>">Edit</a>
  <form method="post" data-confirm="Hapus perusahaan <?= e($r['nama']) ?>?"><?= csrf_field() ?><input type="hidden" name="op" value="hapus"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn sm danger" type="submit">Hapus</button></form></div></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php endif; ?></div>
<?php layout_end();
