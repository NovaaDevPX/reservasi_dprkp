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
  qty INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (ruangan_id, fasilitas_id),
  FOREIGN KEY (ruangan_id) REFERENCES ruangan(id) ON DELETE CASCADE,
  FOREIGN KEY (fasilitas_id) REFERENCES fasilitas(id) ON DELETE CASCADE
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
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (ruangan_id) REFERENCES ruangan(id)
);

-- =========================================
-- RESERVASI FASILITAS
-- =========================================
CREATE TABLE reservasi_fasilitas (
  reservasi_id INT UNSIGNED NOT NULL,
  fasilitas_id INT UNSIGNED NOT NULL,
  qty INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (reservasi_id, fasilitas_id),
  FOREIGN KEY (reservasi_id) REFERENCES reservasi(id) ON DELETE CASCADE,
  FOREIGN KEY (fasilitas_id) REFERENCES fasilitas(id) ON DELETE CASCADE
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
  keterangan TEXT,
  FOREIGN KEY (ruangan_id) REFERENCES ruangan(id)
);

-- =========================================
-- NOTIFIKASI (MULTI USER / MULTI ROLE)
-- =========================================
CREATE TABLE notifikasi (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  reservasi_id INT UNSIGNED NULL,
  judul VARCHAR(150) NOT NULL,
  pesan TEXT NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reservasi_id) REFERENCES reservasi(id) ON DELETE CASCADE
);

-- =========================================
-- DATA USERS
-- =========================================
INSERT INTO users (nip,nama,password,role) VALUES
('pegawai123','Budi Santoso','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','pegawai'),
('pegawai124','Andi Saputra','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','pegawai'),
('pegawai125','Rina Putri','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','pegawai'),
('pegawai126','Doni Pratama','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','pegawai'),
('admin456','Siti Rahmawati','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','admin'),
('kabag789','Hadi Prasetyo','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','kepala_bagian');

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
('Proyektor'),
('AC'),
('Sound System'),
('Video Conference'),
('Whiteboard'),
('Microphone');

-- =========================================
-- RUANGAN FASILITAS + QTY (REALISTIS)
-- =========================================
INSERT INTO ruangan_fasilitas VALUES
(1,1,1),(1,2,2),(1,5,1),(1,6,2),
(2,1,1),(2,2,2),(2,3,1),(2,5,1),(2,6,4),
(3,1,2),(3,2,6),(3,3,1),(3,6,10),
(4,2,1),(4,5,1),
(5,2,1),(5,5,1),
(6,1,1),(6,2,2),(6,3,1),(6,6,4);

-- =========================================
-- DUMMY RESERVASI (JAN–FEB 2026)
-- =========================================
INSERT INTO reservasi
(user_id,ruangan_id,tanggal,jam_mulai,jam_selesai,keperluan,jumlah_peserta,status) VALUES
(1,1,'2026-01-06','08:30','10:00','Rapat koordinasi mingguan',12,'Disetujui'),
(2,2,'2026-01-06','09:00','11:00','Presentasi rencana kerja',25,'Disetujui'),
(3,4,'2026-01-06','13:00','14:30','Diskusi teknis aplikasi',8,'Menunggu Admin'),
(4,5,'2026-01-06','15:00','16:30','Evaluasi kinerja tim',10,'Disetujui'),
(1,3,'2026-01-20','08:00','17:00','Rapat kerja tahunan',200,'Disetujui'),
(6,3,'2026-02-05','08:00','17:00','Seminar nasional',190,'Disetujui');

-- =========================================
-- RESERVASI FASILITAS + QTY
-- =========================================
INSERT INTO reservasi_fasilitas VALUES
(1,1,1),(1,5,1),(1,6,2),
(2,1,1),(2,3,1),(2,6,3),
(3,5,1),
(4,5,1),
(5,1,2),(5,3,1),(5,6,8),
(6,1,2),(6,3,1),(6,6,10);