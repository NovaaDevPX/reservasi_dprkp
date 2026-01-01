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
) ENGINE=InnoDB;

-- =========================================
-- RUANGAN
-- =========================================
CREATE TABLE ruangan (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_ruangan VARCHAR(100) NOT NULL UNIQUE,
  kapasitas INT NOT NULL,
  status ENUM('Aktif','Nonaktif','Perawatan') DEFAULT 'Aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================
-- FASILITAS
-- =========================================
CREATE TABLE fasilitas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================
-- RUANGAN FASILITAS
-- =========================================
CREATE TABLE ruangan_fasilitas (
  ruangan_id INT UNSIGNED NOT NULL,
  fasilitas_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (ruangan_id, fasilitas_id),
  FOREIGN KEY (ruangan_id) REFERENCES ruangan(id) ON DELETE CASCADE,
  FOREIGN KEY (fasilitas_id) REFERENCES fasilitas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

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
  jumlah_peserta INT DEFAULT 0,
  status ENUM(
    'Menunggu Admin',
    'Menunggu Kepala Bagian',
    'Disetujui',
    'Ditolak',
    'Dibatalkan'
  ) DEFAULT 'Menunggu Admin',
  alasan_tolak TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (ruangan_id) REFERENCES ruangan(id)
) ENGINE=InnoDB;

-- =========================================
-- RESERVASI FASILITAS
-- =========================================
CREATE TABLE reservasi_fasilitas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reservasi_id INT UNSIGNED NOT NULL,
  fasilitas_id INT UNSIGNED NOT NULL,
  status ENUM('Menunggu','Disetujui','Ditolak') DEFAULT 'Menunggu',
  catatan_admin TEXT,
  processed_by INT UNSIGNED,
  processed_at TIMESTAMP NULL,
  FOREIGN KEY (reservasi_id) REFERENCES reservasi(id) ON DELETE CASCADE,
  FOREIGN KEY (fasilitas_id) REFERENCES fasilitas(id),
  FOREIGN KEY (processed_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- =========================================
-- JADWAL BLOKIR
-- =========================================
CREATE TABLE jadwal_blokir (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ruangan_id INT UNSIGNED NOT NULL,
  tanggal DATE NOT NULL,
  jam_mulai TIME NOT NULL,
  jam_selesai TIME NOT NULL,
  keterangan TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ruangan_id) REFERENCES ruangan(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================
-- LOG RESERVASI
-- =========================================
CREATE TABLE log_reservasi (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reservasi_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  aksi VARCHAR(100),
  keterangan TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reservasi_id) REFERENCES reservasi(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- =========================================
-- NOTIFIKASI
-- =========================================
CREATE TABLE notifikasi (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  judul VARCHAR(150),
  pesan TEXT,
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================
-- DATA USERS
-- =========================================
INSERT INTO users (nip, nama, password, role) VALUES
('pegawai123','Budi Pegawai','password','pegawai'),
('admin456','Siti Admin','password','admin'),
('kabag789','Bapak Kepala Bagian','password','kepala_bagian');

-- =========================================
-- DATA RUANGAN
-- =========================================
INSERT INTO ruangan (nama_ruangan, kapasitas) VALUES
('Ruang Rapat A',15),
('Ruang Rapat B',30),
('Aula Utama',200);

-- =========================================
-- DATA FASILITAS
-- =========================================
INSERT INTO fasilitas (nama) VALUES
('Proyektor'),
('AC'),
('Sound System'),
('Video Conference'),
('Whiteboard');

-- =========================================
-- RUANGAN FASILITAS DEFAULT
-- =========================================
INSERT INTO ruangan_fasilitas VALUES
(1,1),(1,2),(1,5),
(2,1),(2,4),
(3,2),(3,3);

-- =========================================
-- DATA RESERVASI
-- =========================================
INSERT INTO reservasi
(user_id, ruangan_id, tanggal, jam_mulai, jam_selesai, keperluan, jumlah_peserta, status) VALUES
(1,1,'2026-01-10','09:00','11:00','Rapat koordinasi internal',10,'Menunggu Admin'),
(1,2,'2026-01-11','13:00','15:00','Presentasi proyek',20,'Menunggu Kepala Bagian'),
(1,3,'2026-01-12','08:00','12:00','Sosialisasi program kerja',120,'Disetujui'),
(1,1,'2026-01-13','10:00','12:00','Meeting mendadak',8,'Ditolak');

-- =========================================
-- RESERVASI FASILITAS TAMBAHAN
-- =========================================
INSERT INTO reservasi_fasilitas
(reservasi_id, fasilitas_id, status, catatan_admin, processed_by, processed_at) VALUES
(1,1,'Menunggu',NULL,NULL,NULL),
(1,5,'Menunggu',NULL,NULL,NULL),
(2,4,'Disetujui','VC tersedia',2,NOW()),
(3,3,'Disetujui','Sound system lengkap',2,NOW()),
(4,1,'Ditolak','Bentrok jadwal',2,NOW());

-- =========================================
-- BLOKIR RUANGAN
-- =========================================
INSERT INTO jadwal_blokir
(ruangan_id, tanggal, jam_mulai, jam_selesai, keterangan) VALUES
(1,'2026-01-15','08:00','17:00','Perawatan AC'),
(2,'2026-01-16','12:00','14:00','Dipakai pimpinan'),
(3,'2026-01-17','07:00','18:00','Acara dinas');

-- =========================================
-- LOG RESERVASI
-- =========================================
INSERT INTO log_reservasi
(reservasi_id, user_id, aksi, keterangan) VALUES
(1,1,'Buat Reservasi','Pegawai mengajukan reservasi'),
(2,1,'Buat Reservasi','Pengajuan presentasi'),
(2,2,'Verifikasi Admin','Diteruskan ke Kabag'),
(3,3,'Disetujui','Disetujui Kepala Bagian'),
(4,2,'Ditolak','Bentrok jadwal');

-- =========================================
-- NOTIFIKASI
-- =========================================
INSERT INTO notifikasi
(user_id, judul, pesan, is_read) VALUES
(2,'Reservasi Baru','Pengajuan Ruang Rapat A',0),
(3,'Menunggu Persetujuan','Reservasi Ruang Rapat B',0),
(1,'Reservasi Disetujui','Aula Utama disetujui',1),
(1,'Reservasi Ditolak','Ruang Rapat A ditolak',0);
