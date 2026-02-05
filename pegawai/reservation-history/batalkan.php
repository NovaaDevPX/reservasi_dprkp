<?php
session_start();
include '../../config/koneksi.php';

/* =====================
   AUTH PEGAWAI
===================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pegawai') {
  header("Location: ../../index.php");
  exit;
}

/* =====================
   VALIDASI POST
===================== */
if (!isset($_POST['id'])) {
  header("Location: index.php");
  exit;
}

$id = intval($_POST['id']);
$userId = $_SESSION['id_user'] ?? null;

/* =====================
   AMBIL DATA RESERVASI
===================== */
$q = mysqli_query($koneksi, "
  SELECT r.id, r.status, r.user_id, r.kabag_id,
         u.nama AS nama_pegawai,
         ru.nama_ruangan,
         r.tanggal
  FROM reservasi r
  JOIN users u ON r.user_id = u.id
  JOIN ruangan ru ON r.ruangan_id = ru.id
  WHERE r.id = $id
  LIMIT 1
");

$data = mysqli_fetch_assoc($q);

/* =====================
   VALIDASI HAK AKSES
===================== */
if (
  !$data ||
  $data['status'] !== 'Menunggu Kepala Bagian' ||
  $data['user_id'] != $userId
) {
  header("Location: detail.php?id=$id");
  exit;
}

/* =====================
   UPDATE STATUS RESERVASI
===================== */
mysqli_query($koneksi, "
  UPDATE reservasi
  SET status = 'Dibatalkan'
  WHERE id = $id
");

/* =====================
   NOTIFIKASI ADMIN
===================== */
$admin = mysqli_query($koneksi, "
  SELECT id FROM users WHERE role = 'admin'
");

while ($a = mysqli_fetch_assoc($admin)) {
  mysqli_query($koneksi, "
    INSERT INTO notifikasi (user_id, reservasi_id, judul, pesan)
    VALUES (
      {$a['id']},
      $id,
      'Reservasi Dibatalkan',
      'Reservasi ruangan {$data['nama_ruangan']} pada tanggal " . date('d M Y', strtotime($data['tanggal'])) . " dibatalkan oleh {$data['nama_pegawai']}.'
    )
  ");
}

/* =====================
   NOTIFIKASI KEPALA BAGIAN (JIKA ADA)
===================== */
if (!empty($data['kabag_id'])) {
  mysqli_query($koneksi, "
    INSERT INTO notifikasi (user_id, reservasi_id, judul, pesan)
    VALUES (
      {$data['kabag_id']},
      $id,
      'Reservasi Dibatalkan',
      'Reservasi yang menunggu persetujuan Anda telah dibatalkan oleh {$data['nama_pegawai']}.'
    )
  ");
}

/* =====================
   REDIRECT
===================== */
header("Location: detail.php?id=$id");
exit;
