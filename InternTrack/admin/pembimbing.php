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
        $st = $pdo->prepare('SELECT user_id FROM pembimbing WHERE id=?'); $st->execute([(int) $_POST['id']]); $uid = $st->fetchColumn();
        try {
            if ($uid) { $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$uid]); }
            flash('success', 'Data guru pembimbing dihapus.');
        } catch (PDOException $ex) {
            flash('error', 'Guru tidak bisa dihapus karena masih membimbing siswa atau punya catatan monitoring.');
        }
        redirect('admin/pembimbing.php');
    }
    if ($op === 'simpan') {
        $id = (int) ($_POST['id'] ?? 0);
        $row = ['id' => $id, 'nip' => trim($_POST['nip'] ?? ''), 'nama' => trim($_POST['nama'] ?? ''), 'no_hp' => trim($_POST['no_hp'] ?? '')];
        if ($row['nip'] === '')  { $err['nip'] = 'NIP tidak boleh kosong.'; }
        if ($row['nama'] === '') { $err['nama'] = 'Nama tidak boleh kosong.'; }
        if ($row['no_hp'] !== '' && !preg_match('/^[0-9+\- ]{8,20}$/', $row['no_hp'])) { $err['no_hp'] = 'No. HP hanya berisi angka, minimal 8 digit.'; }
        if (!isset($err['nip'])) {
            $c = $pdo->prepare('SELECT COUNT(*) FROM pembimbing WHERE nip=? AND id<>?'); $c->execute([$row['nip'], $id]);
            if ($c->fetchColumn() > 0) { $err['nip'] = 'NIP sudah terdaftar.'; }
        }
        if (!$err) {
            try {
                $pdo->beginTransaction();
                if ($id) {
                    $uid = $pdo->prepare('SELECT user_id FROM pembimbing WHERE id=?'); $uid->execute([$id]); $uid = $uid->fetchColumn();
                    $pdo->prepare('UPDATE pembimbing SET nip=?, nama=?, no_hp=? WHERE id=?')->execute([$row['nip'], $row['nama'], $row['no_hp'] ?: null, $id]);
                    $pdo->prepare('UPDATE users SET nama=? WHERE id=?')->execute([$row['nama'], $uid]);
                } else {
                    // akun login pembimbing: username = NIP, password awal = guru123
                    $pdo->prepare("INSERT INTO users (username, password, nama, role) VALUES (?,?,?, 'pembimbing')")->execute([$row['nip'], password_hash('guru123', PASSWORD_DEFAULT), $row['nama']]);
                    $uid = $pdo->lastInsertId();
                    $pdo->prepare('INSERT INTO pembimbing (user_id, nip, nama, no_hp) VALUES (?,?,?,?)')->execute([$uid, $row['nip'], $row['nama'], $row['no_hp'] ?: null]);
                }
                $pdo->commit();
                flash('success', $id ? 'Data guru diperbarui.' : 'Guru baru tersimpan. Akun masuk: username NIP, password guru123.');
                redirect('admin/pembimbing.php');
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $err['nip'] = 'NIP ini bentrok dengan username akun lain.';
            }
        }
        $aksi = 'form';
    }
}

if ($aksi === 'form') {
    if (!$row) {
        $id = (int) ($_GET['id'] ?? 0);
        $row = ['id' => 0, 'nip' => '', 'nama' => '', 'no_hp' => ''];
        if ($id) { $st = $pdo->prepare('SELECT * FROM pembimbing WHERE id=?'); $st->execute([$id]); $row = $st->fetch() ?: $row; }
    }
    layout_start($row['id'] ? 'Edit guru pembimbing' : 'Tambah guru pembimbing', 'pembimbing');
    ?>
    <form class="card" method="post" action="<?= url('admin/pembimbing.php') ?>" novalidate data-validate style="max-width:760px">
      <?= csrf_field() ?><input type="hidden" name="op" value="simpan"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
      <div class="formgrid">
        <?= field('nip', 'NIP', $row['nip'], $err, 'text', ['required' => 'required', 'maxlength' => 25]) ?>
        <?= field('nama', 'Nama lengkap dan gelar', $row['nama'], $err, 'text', ['required' => 'required']) ?>
        <?= field('no_hp', 'No. HP', $row['no_hp'], $err, 'tel') ?>
      </div>
      <div class="actions"><button class="btn" type="submit">Simpan guru</button><a class="btn ghost" href="<?= url('admin/pembimbing.php') ?>">Batal</a></div>
    </form>
    <?php
    layout_end(); exit;
}

$q = trim($_GET['q'] ?? '');
$st = $pdo->prepare("SELECT b.*, (SELECT COUNT(*) FROM penempatan pn WHERE pn.pembimbing_id=b.id) AS jml FROM pembimbing b WHERE b.nama LIKE ? OR b.nip LIKE ? ORDER BY b.nama");
$st->execute(["%$q%", "%$q%"]); $rows = $st->fetchAll();
layout_start('Guru pembimbing', 'pembimbing', count($rows) . ' guru pembimbing.');
?>
<form class="card filters" method="get">
  <div class="field grow"><label for="q">Cari nama atau NIP</label><input id="q" name="q" value="<?= e($q) ?>"></div>
  <div class="actions"><button class="btn" type="submit">Cari</button><a class="btn ghost" href="<?= url('admin/pembimbing.php') ?>">Atur ulang</a></div>
</form>
<div class="toolbar"><span></span><a class="btn" href="<?= url('admin/pembimbing.php?aksi=form') ?>">Tambah guru</a></div>
<div class="card">
<?php if (!$rows): ?><div class="empty">Tidak ada guru yang cocok dengan pencarian.</div><?php else: ?>
<div class="tablewrap"><table class="tbl"><thead><tr><th>NIP</th><th>Nama</th><th>No. HP</th><th class="num">Siswa bimbingan</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
  <tr><td><?= e($r['nip']) ?></td><td><?= e($r['nama']) ?></td><td><?= e($r['no_hp'] ?: '-') ?></td><td class="num"><?= (int) $r['jml'] ?></td>
  <td><div class="actions"><a class="btn sm ghost" href="<?= url('admin/pembimbing.php?aksi=form&id=' . $r['id']) ?>">Edit</a>
  <form method="post" data-confirm="Hapus guru <?= e($r['nama']) ?>?"><?= csrf_field() ?><input type="hidden" name="op" value="hapus"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn sm danger" type="submit">Hapus</button></form></div></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php endif; ?></div>
<?php layout_end();
