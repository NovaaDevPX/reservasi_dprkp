-- =========================================
-- RESET DATABASE
-- =========================================
DROP DATABASE IF EXISTS reservasi_dprkp;
CREATE DATABASE reservasi_dprkp
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;
USE reservasi_dprkp;

-- =========================================
-- USERS
-- =========================================
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nip VARCHAR(50) NOT NULL UNIQUE,
  nama VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('pegawai','admin','kepala_bagian') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================
-- RUANGAN
-- =========================================
CREATE TABLE ruangan (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_ruangan VARCHAR(100) NOT NULL UNIQUE,
  kapasitas INT NOT NULL,
  status ENUM('Aktif','Nonaktif','Perawatan') DEFAULT 'Aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================
-- FASILITAS
-- =========================================
CREATE TABLE fasilitas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================
-- RUANGAN FASILITAS
-- =========================================
CREATE TABLE ruangan_fasilitas (
  ruangan_id INT UNSIGNED NOT NULL,
  fasilitas_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (ruangan_id, fasilitas_id)
);

-- =========================================
-- RESERVASI
-- =========================================
CREATE TABLE reservasi (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  ruangan_id INT UNSIGNED NOT NULL,
  tanggal DATE NOT NULL,
  jam_mulai TIME NOT NULL,
  jam_selesai TIME NOT NULL,
  keperluan TEXT NOT NULL,
  jumlah_peserta INT,
  status ENUM(
    'Menunggu Admin',
    'Menunggu Kepala Bagian',
    'Disetujui',
    'Ditolak',
    'Dibatalkan'
  ) DEFAULT 'Menunggu Admin',
  alasan_tolak TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================
-- JADWAL BLOKIR
-- =========================================
CREATE TABLE jadwal_blokir (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ruangan_id INT UNSIGNED,
  tanggal DATE,
  jam_mulai TIME,
  jam_selesai TIME,
  keterangan TEXT
);

-- =========================================
-- DATA USERS
-- =========================================
INSERT INTO users (nip,nama,password,role) VALUES
('pegawai123','Budi Pegawai','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','pegawai'),
('pegawai124','Andi Saputra','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','pegawai'),
('pegawai125','Rina Putri','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','pegawai'),
('pegawai126','Doni Pratama','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','pegawai'),
('admin456','Siti Admin','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','admin'),
('kabag789','Kepala Bagian','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','kepala_bagian');

-- =========================================
-- DATA RUANGAN
-- =========================================
INSERT INTO ruangan (nama_ruangan,kapasitas) VALUES
('Ruang Rapat A',15),
('Ruang Rapat B',30),
('Aula Utama',200),
('Ruang Diskusi 1',10),
('Ruang Diskusi 2',12),
('Ruang Training',50);

-- =========================================
-- DATA FASILITAS
-- =========================================
INSERT INTO fasilitas (nama) VALUES
('Proyektor'),('AC'),('Sound System'),('Video Conference'),('Whiteboard');

-- =========================================
-- DUMMY RESERVASI REALISTIS (PADAT)
-- JANUARI - FEBRUARI 2026
-- =========================================
INSERT INTO reservasi
(user_id,ruangan_id,tanggal,jam_mulai,jam_selesai,keperluan,jumlah_peserta,status) VALUES

-- JANUARI MINGGU 1
(1,1,'2026-01-06','08:30','10:00','Rapat koordinasi mingguan',12,'Disetujui'),
(2,2,'2026-01-06','09:00','11:00','Presentasi rencana kerja',25,'Disetujui'),
(3,4,'2026-01-06','13:00','14:30','Diskusi teknis aplikasi',8,'Menunggu Admin'),
(4,5,'2026-01-06','15:00','16:30','Evaluasi kinerja tim',10,'Disetujui'),

(1,1,'2026-01-07','09:00','11:00','Rapat lintas bidang',15,'Disetujui'),
(2,3,'2026-01-07','08:00','12:00','Sosialisasi kebijakan baru',150,'Disetujui'),
(4,4,'2026-01-07','13:00','15:00','Diskusi anggaran',6,'Ditolak'),

-- JANUARI TENGAH
(1,1,'2026-01-13','08:30','10:00','Koordinasi awal tahun',10,'Disetujui'),
(2,1,'2026-01-13','09:00','11:00','Rapat divisi keuangan',14,'Ditolak'),
(3,2,'2026-01-13','13:00','15:00','Presentasi vendor IT',20,'Disetujui'),
(4,5,'2026-01-13','15:30','17:00','Review kontrak',8,'Menunggu Admin'),

(5,4,'2026-01-14','09:00','10:30','Diskusi teknis jaringan',6,'Disetujui'),
(6,6,'2026-01-14','08:00','12:00','Pelatihan keamanan data',40,'Menunggu Kepala Bagian'),

-- AKHIR JANUARI
(1,3,'2026-01-20','08:00','17:00','Rapat kerja tahunan',200,'Disetujui'),
(3,1,'2026-01-20','10:00','12:00','Koordinasi kecil',6,'Dibatalkan'),
(4,4,'2026-01-20','13:00','14:30','Diskusi desain sistem',7,'Disetujui'),

-- FEBRUARI
(1,1,'2026-02-03','08:30','10:00','Rapat koordinasi rutin',12,'Disetujui'),
(2,2,'2026-02-03','10:30','12:00','Presentasi laporan',20,'Disetujui'),
(3,4,'2026-02-03','13:00','14:30','Diskusi sistem baru',8,'Menunggu Admin'),

(4,6,'2026-02-04','08:00','12:00','Pelatihan pegawai baru',35,'Disetujui'),
(5,1,'2026-02-04','13:00','15:00','Rapat internal kecil',10,'Ditolak'),

(6,3,'2026-02-05','08:00','17:00','Seminar nasional',190,'Disetujui');


INSERT INTO ruangan_fasilitas (ruangan_id, fasilitas_id) VALUES

-- Ruang Rapat A
(1,1), 
(1,2), 
(1,3), 
(1,4), 
(1,5), 
(2,1),
(2,2),
(2,3),
(2,4),
(2,5),
(3,2),
(3,3),
(3,1),
(4,2),
(4,5),
(5,2),
(5,5),
(6,1),
(6,2),
(6,3),
(6,5);
