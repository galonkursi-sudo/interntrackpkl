INTERNTRACK - Sistem Monitoring Kegiatan PKL Siswa
==================================================
Kebutuhan : XAMPP/Laragon (PHP 7.4 atau lebih baru, disarankan 8.x) dan MySQL/MariaDB.

Cara menjalankan
1. Salin folder InternTrack ke htdocs (XAMPP) atau www (Laragon).
2. Nyalakan Apache dan MySQL.
3. Buka phpMyAdmin > tab Import > pilih database/interntrack.sql > Go.
   (File ini membuat database "interntrack", semua tabel, relasi, dan data dummy.)
4. Jika user/password MySQL berbeda, ubah config/db.php.
5. Buka http://localhost/InternTrack/ di browser.

Akun contoh
  Admin       : admin / admin123
  Pembimbing  : guru01 sampai guru10 / guru123
  Siswa       : 2526001 sampai 2526025 (NIS) / siswa123
Siswa atau guru yang ditambah lewat aplikasi otomatis punya akun:
  username = NIS atau NIP, password awal = siswa123 atau guru123.

Struktur folder
  config/   koneksi database dan fungsi bantu (algoritma status PKL ada di helpers.php)
  assets/   css dan javascript
  auth/     login dan logout
  admin/    dashboard dan modul admin/pembimbing
  siswa/    halaman siswa
  laporan/  laporan data siswa, kehadiran, monitoring
  database/ interntrack.sql
  index.php pintu masuk aplikasi
