<?php
require_once __DIR__ . '/../config/helpers.php';
require_login(['siswa']);
$me = siswa_saya();
$st = db()->prepare('SELECT m.*, pb.nama AS pembimbing FROM monitoring m JOIN pembimbing pb ON pb.id=m.pembimbing_id WHERE m.siswa_id=? ORDER BY m.tanggal DESC, m.id DESC');
$st->execute([$me['id']]); $rows = $st->fetchAll();
layout_start('Hasil monitoring', 'monitoring', 'Catatan, nilai, dan rekomendasi dari guru pembimbing.');
if (!$rows): ?><div class="empty">Pembimbing belum mencatat monitoring untukmu.</div>
<?php else: foreach ($rows as $r): ?>
  <section class="card"><div class="toolbar" style="margin:0 0 10px"><div><h2><?= tgl($r['tanggal']) ?></h2><span class="note">Pembimbing: <?= e($r['pembimbing']) ?></span></div><div class="big"><?= (int) $r['nilai'] ?></div></div>
  <p><strong>Catatan:</strong> <?= e($r['catatan']) ?></p><p><strong>Rekomendasi:</strong> <?= e($r['rekomendasi'] ?: '-') ?></p></section>
<?php endforeach; endif;
layout_end();
