-- InternTrack - Sistem Monitoring Kegiatan PKL Siswa
-- Database relasional MySQL/MariaDB. Impor file ini lewat phpMyAdmin (tab Import).
CREATE DATABASE IF NOT EXISTS interntrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE interntrack;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS monitoring, absensi, jurnal, penempatan, pembimbing, perusahaan, siswa, users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  role ENUM('admin','pembimbing','siswa') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB;

CREATE TABLE siswa (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  nis VARCHAR(20) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  kelas VARCHAR(20) NOT NULL,
  jurusan VARCHAR(60) NOT NULL,
  no_hp VARCHAR(20) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_siswa_nis (nis),
  UNIQUE KEY uq_siswa_user (user_id),
  CONSTRAINT fk_siswa_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE perusahaan (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama VARCHAR(120) NOT NULL,
  alamat TEXT NOT NULL,
  bidang VARCHAR(80) NOT NULL,
  kontak VARCHAR(50) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE pembimbing (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  nip VARCHAR(25) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  no_hp VARCHAR(20) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pembimbing_nip (nip),
  UNIQUE KEY uq_pembimbing_user (user_id),
  CONSTRAINT fk_pembimbing_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE penempatan (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  siswa_id INT UNSIGNED NOT NULL,
  perusahaan_id INT UNSIGNED NOT NULL,
  pembimbing_id INT UNSIGNED NOT NULL,
  tanggal_mulai DATE NOT NULL,
  tanggal_selesai DATE NOT NULL,
  status ENUM('Belum Mulai','Aktif','Selesai') NOT NULL DEFAULT 'Belum Mulai',
  PRIMARY KEY (id),
  UNIQUE KEY uq_penempatan_siswa (siswa_id),
  CONSTRAINT fk_pn_siswa FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_pn_perusahaan FOREIGN KEY (perusahaan_id) REFERENCES perusahaan(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_pn_pembimbing FOREIGN KEY (pembimbing_id) REFERENCES pembimbing(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE jurnal (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  siswa_id INT UNSIGNED NOT NULL,
  tanggal DATE NOT NULL,
  kegiatan TEXT NOT NULL,
  hasil TEXT NULL,
  kendala TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_jurnal_tanggal (tanggal),
  CONSTRAINT fk_jurnal_siswa FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE absensi (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  siswa_id INT UNSIGNED NOT NULL,
  tanggal DATE NOT NULL,
  status ENUM('Hadir','Izin','Sakit','Alpa') NOT NULL,
  keterangan VARCHAR(200) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_absensi_harian (siswa_id, tanggal),
  CONSTRAINT fk_absensi_siswa FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE monitoring (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  siswa_id INT UNSIGNED NOT NULL,
  pembimbing_id INT UNSIGNED NOT NULL,
  tanggal DATE NOT NULL,
  catatan TEXT NOT NULL,
  nilai TINYINT UNSIGNED NOT NULL,
  rekomendasi TEXT NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_mon_siswa FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_mon_pembimbing FOREIGN KEY (pembimbing_id) REFERENCES pembimbing(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ===== DATA DUMMY =====
-- Akun contoh: admin/admin123 | guru01..guru10/guru123 | siswa: NIS (2526001..2526025)/siswa123

INSERT INTO users (id,username,password,nama,role) VALUES
(1,'admin','$2y$12$z539TXHfZWO0LIXgsI34/uvA5DUVGEAGR60woxLjMZFOghf1aP7RK','Administrator PKL','admin'),
(2,'guru01','$2y$12$7Byi4SaUmOV85oUDhRg0heDLdGQEZZ6iuuS3.iThSYA59hj5GVWMe','Drs. Slamet Riyadi','pembimbing'),
(3,'guru02','$2y$12$7Byi4SaUmOV85oUDhRg0heDLdGQEZZ6iuuS3.iThSYA59hj5GVWMe','Ratna Dewi, S.Kom.','pembimbing'),
(4,'guru03','$2y$12$7Byi4SaUmOV85oUDhRg0heDLdGQEZZ6iuuS3.iThSYA59hj5GVWMe','Agus Santoso, S.Pd.','pembimbing'),
(5,'guru04','$2y$12$7Byi4SaUmOV85oUDhRg0heDLdGQEZZ6iuuS3.iThSYA59hj5GVWMe','Heni Kurniawati, S.Kom.','pembimbing'),
(6,'guru05','$2y$12$7Byi4SaUmOV85oUDhRg0heDLdGQEZZ6iuuS3.iThSYA59hj5GVWMe','Bambang Setiawan, S.T.','pembimbing'),
(7,'guru06','$2y$12$7Byi4SaUmOV85oUDhRg0heDLdGQEZZ6iuuS3.iThSYA59hj5GVWMe','Lilis Suryani, S.Pd.','pembimbing'),
(8,'guru07','$2y$12$7Byi4SaUmOV85oUDhRg0heDLdGQEZZ6iuuS3.iThSYA59hj5GVWMe','Yusuf Anshori, S.Kom.','pembimbing'),
(9,'guru08','$2y$12$7Byi4SaUmOV85oUDhRg0heDLdGQEZZ6iuuS3.iThSYA59hj5GVWMe','Maya Kusuma, S.Pd.','pembimbing'),
(10,'guru09','$2y$12$7Byi4SaUmOV85oUDhRg0heDLdGQEZZ6iuuS3.iThSYA59hj5GVWMe','Hendra Gunawan, S.T.','pembimbing'),
(11,'guru10','$2y$12$7Byi4SaUmOV85oUDhRg0heDLdGQEZZ6iuuS3.iThSYA59hj5GVWMe','Sri Wahyuni, S.Kom.','pembimbing'),
(12,'2526001','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Aditya Pratama','siswa'),
(13,'2526002','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Bunga Citra Lestari','siswa'),
(14,'2526003','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Cahyo Nugroho','siswa'),
(15,'2526004','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Dewi Anggraini','siswa'),
(16,'2526005','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Eko Prasetyo','siswa'),
(17,'2526006','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Fitriani Putri','siswa'),
(18,'2526007','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Galih Ramadhan','siswa'),
(19,'2526008','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Hana Salsabila','siswa'),
(20,'2526009','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Indra Wijaya','siswa'),
(21,'2526010','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Jihan Maharani','siswa'),
(22,'2526011','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Kevin Saputra','siswa'),
(23,'2526012','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Laila Nur Azizah','siswa'),
(24,'2526013','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Mulyadi Hakim','siswa'),
(25,'2526014','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Nabila Safitri','siswa'),
(26,'2526015','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Oka Firmansyah','siswa'),
(27,'2526016','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Putri Ayu Lestari','siswa'),
(28,'2526017','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Qori Ananda','siswa'),
(29,'2526018','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Rizky Maulana','siswa'),
(30,'2526019','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Siti Khodijah','siswa'),
(31,'2526020','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Taufik Hidayat','siswa'),
(32,'2526021','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Umi Kulsum','siswa'),
(33,'2526022','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Vino Aditama','siswa'),
(34,'2526023','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Wulan Purnamasari','siswa'),
(35,'2526024','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Yoga Permana','siswa'),
(36,'2526025','$2y$12$AiR7D8vL55CMiieCD7sXOONN.W.bwnCmO00AHyTvWgxr9hWeM7gRG','Zahra Kamila','siswa');

INSERT INTO siswa (id,user_id,nis,nama,kelas,jurusan,no_hp) VALUES
(1,12,'2526001','Aditya Pratama','XII RPL 1','Rekayasa Perangkat Lunak','08123400100'),
(2,13,'2526002','Bunga Citra Lestari','XII RPL 1','Rekayasa Perangkat Lunak','08123400137'),
(3,14,'2526003','Cahyo Nugroho','XII RPL 1','Rekayasa Perangkat Lunak','08123400174'),
(4,15,'2526004','Dewi Anggraini','XII RPL 1','Rekayasa Perangkat Lunak','08123400211'),
(5,16,'2526005','Eko Prasetyo','XII RPL 1','Rekayasa Perangkat Lunak','08123400248'),
(6,17,'2526006','Fitriani Putri','XII RPL 2','Rekayasa Perangkat Lunak','08123400285'),
(7,18,'2526007','Galih Ramadhan','XII RPL 2','Rekayasa Perangkat Lunak','08123400322'),
(8,19,'2526008','Hana Salsabila','XII RPL 2','Rekayasa Perangkat Lunak','08123400359'),
(9,20,'2526009','Indra Wijaya','XII RPL 2','Rekayasa Perangkat Lunak','08123400396'),
(10,21,'2526010','Jihan Maharani','XII RPL 2','Rekayasa Perangkat Lunak','08123400433'),
(11,22,'2526011','Kevin Saputra','XII TKJ 1','Teknik Komputer dan Jaringan','08123400470'),
(12,23,'2526012','Laila Nur Azizah','XII TKJ 1','Teknik Komputer dan Jaringan','08123400507'),
(13,24,'2526013','Mulyadi Hakim','XII TKJ 1','Teknik Komputer dan Jaringan','08123400544'),
(14,25,'2526014','Nabila Safitri','XII TKJ 1','Teknik Komputer dan Jaringan','08123400581'),
(15,26,'2526015','Oka Firmansyah','XII TKJ 1','Teknik Komputer dan Jaringan','08123400618'),
(16,27,'2526016','Putri Ayu Lestari','XII TKJ 2','Teknik Komputer dan Jaringan','08123400655'),
(17,28,'2526017','Qori Ananda','XII TKJ 2','Teknik Komputer dan Jaringan','08123400692'),
(18,29,'2526018','Rizky Maulana','XII TKJ 2','Teknik Komputer dan Jaringan','08123400729'),
(19,30,'2526019','Siti Khodijah','XII TKJ 2','Teknik Komputer dan Jaringan','08123400766'),
(20,31,'2526020','Taufik Hidayat','XII TKJ 2','Teknik Komputer dan Jaringan','08123400803'),
(21,32,'2526021','Umi Kulsum','XII MM 1','Multimedia','08123400840'),
(22,33,'2526022','Vino Aditama','XII MM 1','Multimedia','08123400877'),
(23,34,'2526023','Wulan Purnamasari','XII MM 1','Multimedia','08123400914'),
(24,35,'2526024','Yoga Permana','XII MM 1','Multimedia','08123400951'),
(25,36,'2526025','Zahra Kamila','XII MM 1','Multimedia','08123400988');

INSERT INTO perusahaan (id,nama,alamat,bidang,kontak) VALUES
(1,'PT Nusantara Digital Solusi','Jl. Gatot Subroto No. 21, Semarang','Software House','024-7611201'),
(2,'CV Banyumas Kreatif Media','Jl. Jenderal Sudirman No. 88, Purwokerto','Multimedia & Percetakan','0281-632110'),
(3,'PT Mitra Jaringan Utama','Jl. Pemuda No. 14, Semarang','Jaringan & Telekomunikasi','024-8412099'),
(4,'Dinas Komunikasi dan Informatika Kab. Banyumas','Jl. Rumah Dinas Bupati No. 1, Purwokerto','Instansi Pemerintah','0281-635001'),
(5,'PT Cipta Aplikasi Mandiri','Jl. Ahmad Yani No. 102, Surakarta','Pengembangan Aplikasi','0271-714455'),
(6,'Toko Komputer Maju Jaya','Jl. Veteran No. 7, Purwokerto','Retail & Servis Komputer','0281-640778'),
(7,'PT Lintas Data Persada','Jl. MT Haryono No. 55, Semarang','Data Center & Hosting','024-3560123'),
(8,'Studio Kreasi Visual','Jl. Kaliurang Km 5, Yogyakarta','Desain Grafis & Animasi','0274-889021'),
(9,'Bank Perkreditan Rakyat Sejahtera','Jl. Diponegoro No. 30, Salatiga','Perbankan','0298-322145'),
(10,'Startup Dagang Digital','Jl. Pandanaran No. 9, Semarang','E-commerce','024-8319900');

INSERT INTO pembimbing (id,user_id,nip,nama,no_hp) VALUES
(1,2,'19800101012010011001','Drs. Slamet Riyadi','08571220000'),
(2,3,'19800101022010011001','Ratna Dewi, S.Kom.','08571220053'),
(3,4,'19800101032010011001','Agus Santoso, S.Pd.','08571220106'),
(4,5,'19800101042010011001','Heni Kurniawati, S.Kom.','08571220159'),
(5,6,'19800101052010011001','Bambang Setiawan, S.T.','08571220212'),
(6,7,'19800101062010011001','Lilis Suryani, S.Pd.','08571220265'),
(7,8,'19800101072010011001','Yusuf Anshori, S.Kom.','08571220318'),
(8,9,'19800101082010011001','Maya Kusuma, S.Pd.','08571220371'),
(9,10,'19800101092010011001','Hendra Gunawan, S.T.','08571220424'),
(10,11,'19800101102010011001','Sri Wahyuni, S.Kom.','08571220477');

INSERT INTO penempatan (id,siswa_id,perusahaan_id,pembimbing_id,tanggal_mulai,tanggal_selesai,status) VALUES
(1,1,1,1,'2026-09-01','2026-12-04','Aktif'),
(2,2,2,2,'2026-09-01','2026-12-04','Aktif'),
(3,3,3,3,'2026-09-01','2026-12-04','Aktif'),
(4,4,4,4,'2026-09-01','2026-12-04','Aktif'),
(5,5,5,5,'2026-09-01','2026-12-04','Aktif'),
(6,6,6,6,'2026-09-01','2026-12-04','Aktif'),
(7,7,7,7,'2026-06-01','2026-08-28','Selesai'),
(8,8,8,8,'2026-06-01','2026-08-28','Selesai'),
(9,9,9,9,'2026-11-02','2027-01-29','Belum Mulai'),
(10,10,10,10,'2026-11-02','2027-01-29','Belum Mulai');

INSERT INTO jurnal (id,siswa_id,tanggal,kegiatan,hasil,kendala) VALUES
(1,1,'2026-10-06','Instalasi dan konfigurasi aplikasi kantor','Aplikasi terpasang di 5 komputer','Lisensi belum tersedia'),
(2,1,'2026-10-07','Membuat desain halaman web perusahaan','Mockup halaman beranda selesai','Menyesuaikan warna dengan identitas perusahaan'),
(3,1,'2026-10-08','Mengkonfigurasi router dan switch ruang kerja','Jaringan lantai 2 tersambung','Kabel LAN banyak yang rusak'),
(4,1,CURDATE(),'Membuat basis data pelanggan','ERD dan tabel selesai dibuat','Masih bingung menentukan relasi tabel'),
(5,2,'2026-10-07','Belajar framework PHP untuk modul laporan','Halaman laporan dasar berjalan','Dokumentasi bahasa Inggris sulit dipahami'),
(6,2,'2026-10-08','Merakit dan menguji komputer pelanggan','3 unit komputer lolos uji','Komponen belum lengkap'),
(7,2,'2026-10-09','Memeriksa server dan membuat backup data','Backup mingguan berjalan otomatis','Akses ruang server terbatas'),
(8,2,CURDATE(),'Mengedit video promosi produk','Video berdurasi 1 menit selesai','Rendering memakan waktu lama'),
(9,3,'2026-10-08','Membantu input data nasabah ke sistem','120 data berhasil diinput','Format data tidak seragam'),
(10,3,'2026-10-09','Menguji fitur keranjang belanja','Ditemukan 4 bug dan dilaporkan','Skenario uji belum lengkap'),
(11,3,'2026-10-12','Menyusun dokumentasi penggunaan aplikasi','Manual pengguna 10 halaman selesai','Tangkapan layar harus diulang'),
(12,3,CURDATE(),'Merapikan struktur folder proyek','Struktur proyek lebih terorganisir','-'),
(13,4,'2026-10-09','Instalasi dan konfigurasi aplikasi kantor','Aplikasi terpasang di 5 komputer','Lisensi belum tersedia'),
(14,4,'2026-10-12','Membuat desain halaman web perusahaan','Mockup halaman beranda selesai','Menyesuaikan warna dengan identitas perusahaan'),
(15,4,'2026-10-13','Mengkonfigurasi router dan switch ruang kerja','Jaringan lantai 2 tersambung','Kabel LAN banyak yang rusak'),
(16,4,CURDATE(),'Membuat basis data pelanggan','ERD dan tabel selesai dibuat','Masih bingung menentukan relasi tabel'),
(17,5,'2026-10-12','Belajar framework PHP untuk modul laporan','Halaman laporan dasar berjalan','Dokumentasi bahasa Inggris sulit dipahami'),
(18,5,'2026-10-13','Merakit dan menguji komputer pelanggan','3 unit komputer lolos uji','Komponen belum lengkap'),
(19,5,'2026-10-14','Memeriksa server dan membuat backup data','Backup mingguan berjalan otomatis','Akses ruang server terbatas'),
(20,5,CURDATE(),'Mengedit video promosi produk','Video berdurasi 1 menit selesai','Rendering memakan waktu lama'),
(21,6,'2026-10-13','Membantu input data nasabah ke sistem','120 data berhasil diinput','Format data tidak seragam'),
(22,6,'2026-10-14','Menguji fitur keranjang belanja','Ditemukan 4 bug dan dilaporkan','Skenario uji belum lengkap'),
(23,6,'2026-10-05','Menyusun dokumentasi penggunaan aplikasi','Manual pengguna 10 halaman selesai','Tangkapan layar harus diulang'),
(24,6,'2026-10-06','Merapikan struktur folder proyek','Struktur proyek lebih terorganisir','-'),
(25,7,'2026-08-10','Instalasi dan konfigurasi aplikasi kantor','Aplikasi terpasang di 5 komputer','Lisensi belum tersedia'),
(26,7,'2026-08-12','Membuat desain halaman web perusahaan','Mockup halaman beranda selesai','Menyesuaikan warna dengan identitas perusahaan'),
(27,7,'2026-08-14','Mengkonfigurasi router dan switch ruang kerja','Jaringan lantai 2 tersambung','Kabel LAN banyak yang rusak'),
(28,8,'2026-08-10','Membuat basis data pelanggan','ERD dan tabel selesai dibuat','Masih bingung menentukan relasi tabel'),
(29,8,'2026-08-12','Belajar framework PHP untuk modul laporan','Halaman laporan dasar berjalan','Dokumentasi bahasa Inggris sulit dipahami'),
(30,8,'2026-08-14','Merakit dan menguji komputer pelanggan','3 unit komputer lolos uji','Komponen belum lengkap');

INSERT INTO absensi (id,siswa_id,tanggal,status,keterangan) VALUES
(1,1,'2026-10-05','Hadir','-'),
(2,1,'2026-10-06','Hadir','-'),
(3,1,'2026-10-07','Hadir','-'),
(4,1,'2026-10-08','Hadir','-'),
(5,1,'2026-10-09','Hadir','-'),
(6,1,'2026-10-12','Hadir','-'),
(7,2,'2026-10-05','Hadir','-'),
(8,2,'2026-10-06','Hadir','-'),
(9,2,'2026-10-07','Izin','Dengan keterangan'),
(10,2,'2026-10-08','Hadir','-'),
(11,2,'2026-10-09','Hadir','-'),
(12,2,'2026-10-12','Hadir','-'),
(13,3,'2026-10-05','Hadir','-'),
(14,3,'2026-10-06','Sakit','Dengan keterangan'),
(15,3,'2026-10-07','Hadir','-'),
(16,3,'2026-10-08','Alpa','Dengan keterangan'),
(17,3,'2026-10-09','Hadir','-'),
(18,3,'2026-10-12','Hadir','-'),
(19,4,'2026-10-05','Hadir','-'),
(20,4,'2026-10-06','Izin','Dengan keterangan'),
(21,4,'2026-10-07','Alpa','Dengan keterangan'),
(22,4,'2026-10-08','Hadir','-'),
(23,4,'2026-10-09','Izin','Dengan keterangan'),
(24,4,'2026-10-12','Hadir','-'),
(25,5,'2026-10-05','Hadir','-'),
(26,5,'2026-10-06','Hadir','-'),
(27,5,'2026-10-07','Hadir','-'),
(28,5,'2026-10-08','Hadir','-'),
(29,5,'2026-10-09','Hadir','-'),
(30,5,'2026-10-12','Hadir','-');

INSERT INTO monitoring (id,siswa_id,pembimbing_id,tanggal,catatan,nilai,rekomendasi) VALUES
(1,1,1,'2026-10-09','Siswa mampu berkomunikasi dengan baik dan dapat menyelesaikan tugas yang diberikan industri.',85,'Pertahankan kedisiplinan dan mulai ambil tugas yang lebih menantang.'),
(2,2,2,'2026-10-09','Siswa rajin dan cepat belajar, namun perlu lebih teliti dalam pengerjaan.',80,'Biasakan memeriksa ulang pekerjaan sebelum dikumpulkan.'),
(3,3,3,'2026-10-09','Siswa aktif bertanya dan mampu bekerja dalam tim.',88,'Tingkatkan kemampuan dokumentasi pekerjaan.'),
(4,4,4,'2026-10-09','Kehadiran kurang konsisten, hasil kerja cukup baik.',72,'Perbaiki kehadiran dan ketepatan waktu.'),
(5,5,5,'2026-10-09','Siswa disiplin dan bertanggung jawab pada tugasnya.',90,'Dapat dipercaya memegang tugas mandiri.'),
(6,6,6,'2026-10-09','Pemahaman dasar baik, perlu banyak latihan praktik.',76,'Perbanyak latihan mandiri di luar jam kerja.'),
(7,7,7,'2026-08-28','PKL selesai dengan hasil sangat memuaskan.',92,'Direkomendasikan untuk program magang lanjutan.'),
(8,8,8,'2026-08-28','PKL selesai, kinerja sesuai harapan perusahaan.',84,'Terus kembangkan keterampilan teknis.'),
(9,1,1,'2026-10-16','Monitoring awal: siswa sudah mengenal lingkungan kerja.',78,'Siapkan perlengkapan dan jadwal sebelum mulai.'),
(10,2,2,'2026-10-16','Monitoring awal: siswa antusias dan siap mulai.',80,'Pelajari prosedur keselamatan kerja.');
