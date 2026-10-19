<?php
/**
 * InternTrack - fungsi bantu bersama: sesi, keamanan, perhitungan, dan kerangka tampilan.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

// Alamat dasar aplikasi dideteksi otomatis (mis. /InternTrack di folder htdocs)
$__root = rtrim(str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$__app  = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
define('BASE', ($__root !== '' && strpos($__app, $__root) === 0) ? substr($__app, strlen($__root)) : '/InternTrack');

/* ---------- Umum & keamanan ---------- */
function e($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function url(string $p = ''): string { return BASE . '/' . ltrim($p, '/'); }
function redirect(string $p): void { header('Location: ' . url($p)); exit; }
function user(): ?array { return $_SESSION['user'] ?? null; }
function home_for(string $role): string { return $role === 'siswa' ? 'siswa/index.php' : 'admin/index.php'; }

function flash(?string $type = null, ?string $msg = null)
{
    if ($type !== null) { $_SESSION['flash'] = [$type, $msg]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function require_login(array $roles): void
{
    $u = user();
    if (!$u) { redirect('auth/login.php'); }
    if (!in_array($u['role'], $roles, true)) { redirect(home_for($u['role'])); }
}

function csrf_field(): string
{
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
    return '<input type="hidden" name="_csrf" value="' . $_SESSION['csrf'] . '">';
}

function csrf_check(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf'] ?? '')) {
        http_response_code(419);
        die('Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

/* ---------- Format tanggal ---------- */
function tgl($d): string
{
    if (!$d) { return '-'; }
    $b = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($d);
    return date('j', $t) . ' ' . $b[(int) date('n', $t)] . ' ' . date('Y', $t);
}

function hari_ini_panjang(): string
{
    $h = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $b = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $h[(int) date('w')] . ', ' . date('j') . ' ' . $b[(int) date('n')] . ' ' . date('Y');
}

/* ---------- ALGORITMA 3: penentuan status PKL dari persentase kehadiran ---------- */
function persen_hadir(int $hadir, int $total): float
{
    return $total > 0 ? round($hadir / $total * 100, 1) : 0.0;
}

function status_kehadiran(float $persen): string
{
    if ($persen >= 90) { return 'Sangat Baik'; }
    if ($persen >= 75) { return 'Baik'; }
    if ($persen >= 60) { return 'Perlu Perhatian'; }
    return 'Perlu Pembinaan';
}

/** Rekap kehadiran per siswa. $where hanya boleh memakai kolom tabel siswa (alias s). */
function rekap_kehadiran(string $where = '', array $params = []): array
{
    $sql = "SELECT s.id, s.nis, s.nama, s.kelas, COUNT(a.id) AS total,
                   COALESCE(SUM(a.status='Hadir'),0) AS hadir, COALESCE(SUM(a.status='Izin'),0) AS izin,
                   COALESCE(SUM(a.status='Sakit'),0) AS sakit, COALESCE(SUM(a.status='Alpa'),0) AS alpa
            FROM siswa s LEFT JOIN absensi a ON a.siswa_id = s.id $where
            GROUP BY s.id, s.nis, s.nama, s.kelas ORDER BY s.nama";
    $st = db()->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();
    foreach ($rows as &$r) {
        $r['persen'] = persen_hadir((int) $r['hadir'], (int) $r['total']);
        $r['status'] = $r['total'] > 0 ? status_kehadiran($r['persen']) : 'Belum ada data';
    }
    unset($r);
    return $rows;
}

/** Data siswa + penempatan dengan pencarian (nama/NIS) dan filter kelas, perusahaan, status, pembimbing. */
function query_siswa_pkl(array $f): array
{
    $w = []; $p = [];
    if (($f['q'] ?? '') !== '') { $w[] = '(s.nama LIKE ? OR s.nis LIKE ?)'; $p[] = '%' . $f['q'] . '%'; $p[] = '%' . $f['q'] . '%'; }
    if (($f['kelas'] ?? '') !== '') { $w[] = 's.kelas = ?'; $p[] = $f['kelas']; }
    if (!empty($f['perusahaan'])) { $w[] = 'pn.perusahaan_id = ?'; $p[] = (int) $f['perusahaan']; }
    if (($f['status'] ?? '') !== '') {
        if ($f['status'] === 'Belum Ditempatkan') { $w[] = 'pn.id IS NULL'; }
        else { $w[] = 'pn.status = ?'; $p[] = $f['status']; }
    }
    if (!empty($f['pembimbing'])) { $w[] = 'pn.pembimbing_id = ?'; $p[] = (int) $f['pembimbing']; }
    $sql = "SELECT s.*, pn.status, pn.tanggal_mulai, pn.tanggal_selesai, pr.nama AS perusahaan, pb.nama AS pembimbing
            FROM siswa s
            LEFT JOIN penempatan pn ON pn.siswa_id = s.id
            LEFT JOIN perusahaan pr ON pr.id = pn.perusahaan_id
            LEFT JOIN pembimbing pb ON pb.id = pn.pembimbing_id"
         . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' ORDER BY s.nama';
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st->fetchAll();
}

function siswa_saya(): array
{
    $st = db()->prepare("SELECT s.*, pn.status, pn.tanggal_mulai, pn.tanggal_selesai,
            pr.nama AS perusahaan, pr.alamat AS pr_alamat, pr.bidang AS pr_bidang, pr.kontak AS pr_kontak,
            pb.nama AS pembimbing, pb.no_hp AS pb_hp
        FROM siswa s
        LEFT JOIN penempatan pn ON pn.siswa_id = s.id
        LEFT JOIN perusahaan pr ON pr.id = pn.perusahaan_id
        LEFT JOIN pembimbing pb ON pb.id = pn.pembimbing_id
        WHERE s.user_id = ?");
    $st->execute([user()['id']]);
    $r = $st->fetch();
    if (!$r) { die('Data siswa untuk akun ini belum dibuat. Hubungi admin.'); }
    return $r;
}

function pembimbing_saya(): ?int
{
    $st = db()->prepare('SELECT id FROM pembimbing WHERE user_id = ?');
    $st->execute([user()['id']]);
    $id = $st->fetchColumn();
    return $id ? (int) $id : null;
}

/* ---------- Komponen tampilan ---------- */
function badge_pkl(?string $s): string
{
    if (!$s) { return '<span class="badge b-none">Belum ditempatkan</span>'; }
    $c = ['Aktif' => 'aktif', 'Selesai' => 'selesai', 'Belum Mulai' => 'belum'];
    return '<span class="badge b-' . ($c[$s] ?? 'belum') . '">' . e($s) . '</span>';
}

function badge_hadir(string $s): string
{
    $c = ['Sangat Baik' => 'sb', 'Baik' => 'baik', 'Perlu Perhatian' => 'pp', 'Perlu Pembinaan' => 'pb'];
    return '<span class="badge b-' . ($c[$s] ?? 'none') . '">' . e($s) . '</span>';
}

function badge_abs(string $s): string
{
    $c = ['Hadir' => 'sb', 'Izin' => 'aktif', 'Sakit' => 'pp', 'Alpa' => 'pb'];
    return '<span class="badge b-' . ($c[$s] ?? 'none') . '">' . e($s) . '</span>';
}

function bar_persen(float $p, string $status): string
{
    $c = ['Sangat Baik' => 'sb', 'Baik' => 'baik', 'Perlu Perhatian' => 'pp', 'Perlu Pembinaan' => 'pb'];
    return '<div class="bar"><span class="f-' . ($c[$status] ?? 'none') . '" data-w="' . min(100, $p) . '"></span></div>';
}

function opt_list(array $pairs, $selected = '', string $placeholder = ''): string
{
    $h = $placeholder !== '' ? '<option value="">' . e($placeholder) . '</option>' : '';
    foreach ($pairs as $v => $l) {
        $h .= '<option value="' . e($v) . '"' . ((string) $v === (string) $selected ? ' selected' : '') . '>' . e($l) . '</option>';
    }
    return $h;
}

function field(string $name, string $label, $val = '', array $err = [], string $type = 'text', array $attr = []): string
{
    $a = '';
    foreach ($attr as $k => $v) { $a .= ' ' . $k . '="' . e($v) . '"'; }
    $req = isset($attr['required']) ? ' <abbr title="wajib diisi">*</abbr>' : '';
    return '<div class="field' . (isset($err[$name]) ? ' err' : '') . '"><label for="' . $name . '">' . e($label) . $req . '</label>'
        . '<input id="' . $name . '" name="' . $name . '" type="' . $type . '" value="' . e($val) . '" data-label="' . e($label) . '"' . $a . '>'
        . (isset($err[$name]) ? '<div class="msg-err">' . e($err[$name]) . '</div>' : '') . '</div>';
}

function select_field(string $name, string $label, array $pairs, $sel = '', array $err = [], string $placeholder = 'Pilih...', bool $required = true): string
{
    $req = $required ? ' <abbr title="wajib dipilih">*</abbr>' : '';
    return '<div class="field' . (isset($err[$name]) ? ' err' : '') . '"><label for="' . $name . '">' . e($label) . $req . '</label>'
        . '<select id="' . $name . '" name="' . $name . '" data-label="' . e($label) . '"' . ($required ? ' required' : '') . '>' . opt_list($pairs, $sel, $placeholder) . '</select>'
        . (isset($err[$name]) ? '<div class="msg-err">' . e($err[$name]) . '</div>' : '') . '</div>';
}

function textarea_field(string $name, string $label, $val = '', array $err = [], bool $required = false, int $rows = 3): string
{
    $req = $required ? ' <abbr title="wajib diisi">*</abbr>' : '';
    return '<div class="field' . (isset($err[$name]) ? ' err' : '') . '"><label for="' . $name . '">' . e($label) . $req . '</label>'
        . '<textarea id="' . $name . '" name="' . $name . '" rows="' . $rows . '" data-label="' . e($label) . '"' . ($required ? ' required' : '') . '>' . e($val) . '</textarea>'
        . (isset($err[$name]) ? '<div class="msg-err">' . e($err[$name]) . '</div>' : '') . '</div>';
}

function laporan_tabs(string $active): void
{
    $t = ['siswa' => 'Data siswa PKL', 'kehadiran' => 'Kehadiran', 'monitoring' => 'Monitoring'];
    echo '<nav class="tabs no-print">';
    foreach ($t as $k => $l) {
        echo '<a href="' . url('laporan/' . $k . '.php') . '"' . ($k === $active ? ' class="on"' : '') . '>' . e($l) . '</a>';
    }
    echo '</nav>';
}

function layout_start(string $title, string $active = '', string $sub = ''): void
{
    $u = user();
    $roleLabel = ['admin' => 'Admin', 'pembimbing' => 'Guru pembimbing', 'siswa' => 'Siswa'][$u['role']] ?? '';
    if ($u['role'] === 'siswa') {
        $nav = [['Beranda', 'siswa/index.php', 'beranda'], ['Profil dan tempat PKL', 'siswa/profil.php', 'profil'],
            ['Jurnal kegiatan', 'siswa/jurnal.php', 'jurnal'], ['Kehadiran', 'siswa/absensi.php', 'absensi'],
            ['Hasil monitoring', 'siswa/monitoring.php', 'monitoring']];
    } else {
        $nav = [['Dashboard', 'admin/index.php', 'dashboard'], ['Data siswa', 'admin/siswa.php', 'siswa'],
            ['Data perusahaan', 'admin/perusahaan.php', 'perusahaan'], ['Guru pembimbing', 'admin/pembimbing.php', 'pembimbing'],
            ['Penempatan PKL', 'admin/penempatan.php', 'penempatan'], ['Aktivitas siswa', 'admin/jurnal.php', 'jurnal'],
            ['Kehadiran', 'admin/absensi.php', 'absensi'], ['Monitoring', 'admin/monitoring.php', 'monitoring'],
            ['Laporan', 'laporan/siswa.php', 'laporan']];
    }
    $f = flash();
    ?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> - InternTrack</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<div class="app">
  <aside class="side">
    <a class="brand" href="<?= url(home_for($u['role'])) ?>"><span class="mark">I</span><span>InternTrack</span></a>
    <nav class="nav">
      <?php foreach ($nav as $n): ?>
        <a href="<?= url($n[1]) ?>"<?= $n[2] === $active ? ' class="on"' : '' ?>><?= e($n[0]) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="who">
      <strong><?= e($u['nama']) ?></strong>
      <span><?= e($roleLabel) ?></span>
      <a class="btn sm ghost" href="<?= url('auth/logout.php') ?>">Keluar</a>
    </div>
  </aside>
  <main class="main">
    <div class="stripe"></div>
    <header class="pagehead">
      <div>
        <h1><?= e($title) ?></h1>
        <?php if ($sub !== ''): ?><p class="sub"><?= e($sub) ?></p><?php endif; ?>
      </div>
      <div class="today no-print"><?= e(hari_ini_panjang()) ?></div>
    </header>
    <?php if ($f): ?><div class="alert a-<?= e($f[0]) ?>" data-auto role="status"><?= e($f[1]) ?></div><?php endif; ?>
<?php
}

function layout_end(): void
{
    ?>
  </main>
</div>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
<?php
}
