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
  surat_pengantar VARCHAR(255) NOT NULL,
  status ENUM(
    'Menunggu Admin',
    'Menunggu Kepala Bagian',
    'Disetujui',
    'Ditolak',
    'Dibatalkan'
  ) DEFAULT 'Menunggu Admin',
  alasan_tolak TEXT,
  kabag_id INT UNSIGNED NULL,
  ttd_kabag VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (kabag_id) REFERENCES users(id),
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
('pegawai001','Budi Santoso','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','pegawai'),
('pegawai002','Andi Saputra','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','pegawai'),
('pegawai003','Rina Putri','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','pegawai'),
('pegawai004','Doni Pratama','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','pegawai'),
('pegawai005','Ahmad Fauzi','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','pegawai'),
('pegawai006','Dewi Lestari','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','pegawai'),
('pegawai007','Rizky Maulana','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','pegawai'),
('pegawai008','Sri Wahyuni','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','pegawai'),
('pegawai009','Agus Salim','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','pegawai'),
('pegawai010','Lina Marlina','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','pegawai'),

('admin001','Siti Rahmawati','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','admin'),
('kabag001','Hadi Prasetyo','$2y$10$KujV8ygg8qD1idhl7sCVGuQtvFL5BRAVqfRK9b9bmzJu6uxL2Rtd6','kepala_bagian');


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
-- INSERT INTO reservasi
-- (user_id,ruangan_id,tanggal,jam_mulai,jam_selesai,keperluan,jumlah_peserta,status,kabag_id)
-- SELECT
--   FLOOR(1 + RAND() * 9) AS user_id,
--   FLOOR(1 + RAND() * 6) AS ruangan_id,
--   DATE_ADD('2026-01-01', INTERVAL FLOOR(RAND() * 60) DAY),
--   '08:00',
--   '10:00',
--   'Rapat koordinasi kegiatan rutin',
--   FLOOR(5 + RAND() * 45),
--   ELT(FLOOR(1 + RAND() * 5),
--     'Menunggu Admin',
--     'Menunggu Kepala Bagian',
--     'Disetujui',
--     'Ditolak',
--     'Dibatalkan'
--   ),
--   12
-- FROM information_schema.columns
-- LIMIT 120;

-- =========================================
-- RESERVASI FASILITAS + QTY
-- =========================================
-- INSERT INTO reservasi_fasilitas (reservasi_id, fasilitas_id, qty)
-- SELECT
--     r.id AS reservasi_id,
--     rf.fasilitas_id,
--     CASE
--         WHEN rf.fasilitas_id = 6 THEN LEAST(rf.qty, r.jumlah_peserta)
--         ELSE rf.qty
--     END AS qty
-- FROM reservasi r
-- JOIN ruangan_fasilitas rf 
--     ON rf.ruangan_id = r.ruangan_id
-- WHERE r.status <> 'Ditolak';


INSERT INTO jadwal_blokir VALUES
(NULL,1,'2026-02-10','08:00','12:00','Maintenance AC'),
(NULL,3,'2026-03-05','08:00','17:00','Persiapan acara besar'),
(NULL,6,'2026-04-01','08:00','12:00','Instalasi perangkat');
