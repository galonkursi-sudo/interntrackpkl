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
        $st = $pdo->prepare('SELECT user_id FROM siswa WHERE id = ?');
        $st->execute([(int) $_POST['id']]);
        $uid = $st->fetchColumn();
        if ($uid) { $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]); flash('success', 'Data siswa beserta akunnya sudah dihapus.'); }
        redirect('admin/siswa.php');
    }
    if ($op === 'simpan') {
        $id = (int) ($_POST['id'] ?? 0);
        $row = ['id' => $id, 'nis' => trim($_POST['nis'] ?? ''), 'nama' => trim($_POST['nama'] ?? ''), 'kelas' => trim($_POST['kelas'] ?? ''),
                'jurusan' => trim($_POST['jurusan'] ?? ''), 'no_hp' => trim($_POST['no_hp'] ?? '')];
        if ($row['nis'] === '')     { $err['nis'] = 'NIS tidak boleh kosong.'; }
        if ($row['nama'] === '')    { $err['nama'] = 'Nama tidak boleh kosong.'; }
        if ($row['kelas'] === '')   { $err['kelas'] = 'Kelas wajib diisi.'; }
        if ($row['jurusan'] === '') { $err['jurusan'] = 'Jurusan wajib diisi.'; }
        if ($row['no_hp'] !== '' && !preg_match('/^[0-9+\- ]{8,20}$/', $row['no_hp'])) { $err['no_hp'] = 'No. HP hanya berisi angka, minimal 8 digit.'; }
        if (!isset($err['nis'])) {
            $c = $pdo->prepare('SELECT COUNT(*) FROM siswa WHERE nis = ? AND id <> ?');
            $c->execute([$row['nis'], $id]);
            if ($c->fetchColumn() > 0) { $err['nis'] = 'NIS tidak boleh duplikat. NIS ' . $row['nis'] . ' sudah terdaftar.'; }
        }
        if (!$err) {
            try {
                $pdo->beginTransaction();
                if ($id) {
                    $uid = $pdo->prepare('SELECT user_id FROM siswa WHERE id = ?'); $uid->execute([$id]); $uid = $uid->fetchColumn();
                    $pdo->prepare('UPDATE siswa SET nis=?, nama=?, kelas=?, jurusan=?, no_hp=? WHERE id=?')
                        ->execute([$row['nis'], $row['nama'], $row['kelas'], $row['jurusan'], $row['no_hp'] ?: null, $id]);
                    $pdo->prepare('UPDATE users SET username=?, nama=? WHERE id=?')->execute([$row['nis'], $row['nama'], $uid]);
                } else {
                    // akun login siswa dibuat otomatis: username = NIS, password awal = siswa123
                    $pdo->prepare("INSERT INTO users (username, password, nama, role) VALUES (?,?,?, 'siswa')")
                        ->execute([$row['nis'], password_hash('siswa123', PASSWORD_DEFAULT), $row['nama']]);
                    $uid = $pdo->lastInsertId();
                    $pdo->prepare('INSERT INTO siswa (user_id, nis, nama, kelas, jurusan, no_hp) VALUES (?,?,?,?,?,?)')
                        ->execute([$uid, $row['nis'], $row['nama'], $row['kelas'], $row['jurusan'], $row['no_hp'] ?: null]);
                }
                $pdo->commit();
                flash('success', $id ? 'Data siswa diperbarui.' : 'Siswa baru tersimpan. Akun masuk: username NIS, password siswa123.');
                redirect('admin/siswa.php');
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $err['nis'] = 'NIS ini bentrok dengan username akun lain. Gunakan NIS yang berbeda.';
            }
        }
        $aksi = 'form';
    }
}

/* ---------- Form tambah / edit ---------- */
if ($aksi === 'form') {
    if (!$row) {
        $id = (int) ($_GET['id'] ?? 0);
        $row = ['id' => 0, 'nis' => '', 'nama' => '', 'kelas' => '', 'jurusan' => '', 'no_hp' => ''];
        if ($id) { $st = $pdo->prepare('SELECT * FROM siswa WHERE id=?'); $st->execute([$id]); $row = $st->fetch() ?: $row; }
    }
    layout_start($row['id'] ? 'Edit data siswa' : 'Tambah siswa', 'siswa');
    ?>
    <form class="card" method="post" action="<?= url('admin/siswa.php') ?>" novalidate data-validate style="max-width:760px">
      <?= csrf_field() ?><input type="hidden" name="op" value="simpan"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
      <div class="formgrid">
        <?= field('nis', 'NIS', $row['nis'], $err, 'text', ['required' => 'required', 'maxlength' => 20]) ?>
        <?= field('nama', 'Nama lengkap', $row['nama'], $err, 'text', ['required' => 'required', 'maxlength' => 100]) ?>
        <?= field('kelas', 'Kelas', $row['kelas'], $err, 'text', ['required' => 'required', 'placeholder' => 'contoh: XII RPL 1']) ?>
        <?= field('jurusan', 'Jurusan', $row['jurusan'], $err, 'text', ['required' => 'required', 'placeholder' => 'contoh: Rekayasa Perangkat Lunak']) ?>
        <?= field('no_hp', 'No. HP', $row['no_hp'], $err, 'tel', ['placeholder' => '08xxxxxxxxxx']) ?>
      </div>
      <div class="actions"><button class="btn" type="submit">Simpan siswa</button><a class="btn ghost" href="<?= url('admin/siswa.php') ?>">Batal</a></div>
    </form>
    <?php
    layout_end(); exit;
}

/* ---------- Detail ---------- */
if ($aksi === 'detail') {
    $id = (int) ($_GET['id'] ?? 0);
    $s = null;
    foreach (query_siswa_pkl([]) as $r) { if ((int) $r['id'] === $id) { $s = $r; } }
    if (!$s) { flash('error', 'Data siswa tidak ditemukan.'); redirect('admin/siswa.php'); }
    $rk = rekap_kehadiran('WHERE s.id = ?', [$id])[0];
    $jr = $pdo->prepare('SELECT * FROM jurnal WHERE siswa_id=? ORDER BY tanggal DESC LIMIT 5'); $jr->execute([$id]);
    $mn = $pdo->prepare('SELECT m.*, pb.nama AS pembimbing FROM monitoring m JOIN pembimbing pb ON pb.id=m.pembimbing_id WHERE m.siswa_id=? ORDER BY m.tanggal DESC'); $mn->execute([$id]);
    layout_start('Detail siswa', 'siswa', $s['nama']);
    ?>
    <div class="grid2">
      <section class="card"><h2>Data diri</h2>
        <dl class="kv"><dt>NIS</dt><dd><?= e($s['nis']) ?></dd><dt>Nama</dt><dd><?= e($s['nama']) ?></dd><dt>Kelas</dt><dd><?= e($s['kelas']) ?></dd>
        <dt>Jurusan</dt><dd><?= e($s['jurusan']) ?></dd><dt>No. HP</dt><dd><?= e($s['no_hp'] ?: '-') ?></dd></dl></section>
      <section class="card"><h2>Penempatan PKL</h2>
        <dl class="kv"><dt>Status</dt><dd><?= badge_pkl($s['status']) ?></dd><dt>Perusahaan</dt><dd><?= e($s['perusahaan'] ?: '-') ?></dd>
        <dt>Pembimbing</dt><dd><?= e($s['pembimbing'] ?: '-') ?></dd><dt>Periode</dt><dd><?= $s['status'] ? tgl($s['tanggal_mulai']) . ' sampai ' . tgl($s['tanggal_selesai']) : '-' ?></dd>
        <dt>Kehadiran</dt><dd><?= $rk['total'] ? e($rk['persen']) . '% ' . badge_hadir($rk['status']) : 'Belum ada data' ?></dd></dl></section>
    </div>
    <section class="card"><h2>Jurnal terakhir</h2>
      <?php $j = $jr->fetchAll(); if (!$j): ?><div class="empty">Siswa ini belum menulis jurnal.</div><?php endif;
      foreach ($j as $x): ?><div class="entry"><div class="meta"><?= tgl($x['tanggal']) ?></div><strong><?= e($x['kegiatan']) ?></strong><br><span class="note">Hasil: <?= e($x['hasil'] ?: '-') ?> | Kendala: <?= e($x['kendala'] ?: '-') ?></span></div><?php endforeach; ?></section>
    <section class="card"><h2>Hasil monitoring</h2>
      <?php $m = $mn->fetchAll(); if (!$m): ?><div class="empty">Belum ada monitoring.</div><?php endif;
      foreach ($m as $x): ?><div class="entry"><div class="meta"><?= tgl($x['tanggal']) ?>, <?= e($x['pembimbing']) ?>, nilai <strong><?= (int) $x['nilai'] ?></strong></div><?= e($x['catatan']) ?></div><?php endforeach; ?></section>
    <div class="actions"><a class="btn" href="<?= url('admin/siswa.php?aksi=form&id=' . $id) ?>">Edit siswa</a><a class="btn ghost" href="<?= url('admin/siswa.php') ?>">Kembali ke daftar</a></div>
    <?php
    layout_end(); exit;
}

/* ---------- Daftar + cari + filter ---------- */
$f = ['q' => trim($_GET['q'] ?? ''), 'kelas' => $_GET['kelas'] ?? '', 'perusahaan' => (int) ($_GET['perusahaan'] ?? 0), 'status' => $_GET['status'] ?? '', 'pembimbing' => (int) ($_GET['pembimbing'] ?? 0)];
$rows = query_siswa_pkl($f);
$kelas = $pdo->query('SELECT DISTINCT kelas FROM siswa ORDER BY kelas')->fetchAll(PDO::FETCH_COLUMN);
$pers = $pdo->query('SELECT id, nama FROM perusahaan ORDER BY nama')->fetchAll(PDO::FETCH_KEY_PAIR);
$pemb = $pdo->query('SELECT id, nama FROM pembimbing ORDER BY nama')->fetchAll(PDO::FETCH_KEY_PAIR);
layout_start('Data siswa', 'siswa', count($rows) . ' siswa ditampilkan.');
?>
<form class="card filters" method="get">
  <div class="field grow"><label for="q">Cari nama atau NIS</label><input id="q" name="q" value="<?= e($f['q']) ?>" placeholder="ketik nama atau NIS"></div>
  <div class="field"><label for="kelas">Kelas</label><select id="kelas" name="kelas"><?= opt_list(array_combine($kelas, $kelas), $f['kelas'], 'Semua kelas') ?></select></div>
  <div class="field"><label for="perusahaan">Perusahaan</label><select id="perusahaan" name="perusahaan"><?= opt_list($pers, $f['perusahaan'], 'Semua perusahaan') ?></select></div>
  <div class="field"><label for="status">Status PKL</label><select id="status" name="status"><?= opt_list(['Belum Mulai' => 'Belum Mulai', 'Aktif' => 'Aktif', 'Selesai' => 'Selesai', 'Belum Ditempatkan' => 'Belum ditempatkan'], $f['status'], 'Semua status') ?></select></div>
  <div class="field"><label for="pembimbing">Pembimbing</label><select id="pembimbing" name="pembimbing"><?= opt_list($pemb, $f['pembimbing'], 'Semua pembimbing') ?></select></div>
  <div class="actions"><button class="btn" type="submit">Terapkan</button><a class="btn ghost" href="<?= url('admin/siswa.php') ?>">Atur ulang</a></div>
</form>
<div class="toolbar"><span></span><a class="btn" href="<?= url('admin/siswa.php?aksi=form') ?>">Tambah siswa</a></div>
<div class="card">
<?php if (!$rows): ?><div class="empty">Tidak ada siswa yang cocok. Ubah kata kunci atau atur ulang filter.</div><?php else: ?>
<div class="tablewrap"><table class="tbl"><thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Perusahaan</th><th>Pembimbing</th><th>Status PKL</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
  <tr><td><?= e($r['nis']) ?></td><td><?= e($r['nama']) ?></td><td><?= e($r['kelas']) ?></td><td><?= e($r['perusahaan'] ?: '-') ?></td><td><?= e($r['pembimbing'] ?: '-') ?></td><td><?= badge_pkl($r['status']) ?></td>
  <td><div class="actions"><a class="btn sm ghost" href="<?= url('admin/siswa.php?aksi=detail&id=' . $r['id']) ?>">Detail</a><a class="btn sm ghost" href="<?= url('admin/siswa.php?aksi=form&id=' . $r['id']) ?>">Edit</a>
  <form method="post" data-confirm="Hapus siswa <?= e($r['nama']) ?> beserta jurnal, absensi, dan monitoringnya?"><?= csrf_field() ?><input type="hidden" name="op" value="hapus"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn sm danger" type="submit">Hapus</button></form></div></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php endif; ?></div>
<?php layout_end();
