<?php
// ALGORITMA 1 - Login pengguna: validasi input -> cari user -> cocokkan password -> buat sesi -> arahkan sesuai peran
require_once __DIR__ . '/../config/helpers.php';
if (user()) { redirect(home_for(user()['role'])); }

$err = []; $username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '') { $err['username'] = 'Username wajib diisi.'; }
    if ($password === '') { $err['password'] = 'Password wajib diisi.'; }
    if (!$err) {
        $st = db()->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([$username]);
        $u = $st->fetch();
        if ($u && password_verify($password, $u['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int) $u['id'], 'username' => $u['username'], 'nama' => $u['nama'], 'role' => $u['role']];
            redirect(home_for($u['role']));
        }
        $err['umum'] = 'Username atau password salah.';
    }
}
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk - InternTrack</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="login-body">
<div class="stripe"></div>
<main class="login-wrap">
  <section class="login-intro">
    <div class="brand big"><span class="mark">I</span><span>InternTrack</span></div>
    <h1>Setiap hari PKL tercatat, setiap kemajuan terpantau.</h1>
    <p>Jurnal kegiatan, kehadiran, dan hasil monitoring pembimbing tersimpan rapi dalam satu buku kerja digital.</p>
    
  </section>
  <section class="login-side">
    <div class="strap" aria-hidden="true"></div>
    <form class="idcard" method="post" novalidate data-validate>
      <h2>Masuk ke akun</h2>
      <?php if (isset($err['umum'])): ?><div class="alert a-error" role="alert"><?= e($err['umum']) ?></div><?php endif; ?>
      <?= csrf_field() ?>
      <?= field('username', 'Username', $username, $err, 'text', ['required' => 'required', 'autocomplete' => 'username', 'autofocus' => 'autofocus']) ?>
      <div class="field<?= isset($err['password']) ? ' err' : '' ?>">
        <label for="password">Password <abbr title="wajib diisi">*</abbr></label>
        <input id="password" name="password" type="password" required data-label="Password" autocomplete="current-password">
        <?php if (isset($err['password'])): ?><div class="msg-err"><?= e($err['password']) ?></div><?php endif; ?>
        <button type="button" id="togglePw" class="linkbtn">Tampilkan</button>
      </div>
      <button class="btn block" type="submit">Masuk</button>
    </form>
  </section>
</main>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
