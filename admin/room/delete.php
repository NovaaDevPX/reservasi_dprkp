<?php
session_start();
include '../../config/koneksi.php';
include '../../includes/notification-helper.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../../index.php");
  exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: index.php");
  exit;
}

$id = (int) $_GET['id'];

/* =====================
   AMBIL DATA RUANGAN
===================== */
$qRuangan = mysqli_query($koneksi, "SELECT nama_ruangan FROM ruangan WHERE id=$id");
$ruangan = mysqli_fetch_assoc($qRuangan);

if (!$ruangan) {
  header("Location: index.php");
  exit;
}

$nama_ruangan = $ruangan['nama_ruangan'];

/* =====================
   CEK DIPAKAI RESERVASI
===================== */
$cek = mysqli_query($koneksi, "
  SELECT id FROM reservasi WHERE ruangan_id=$id LIMIT 1
");

if (mysqli_num_rows($cek) > 0) {
  header("Location: index.php?error=used");
  exit;
}

mysqli_begin_transaction($koneksi);

try {

  // hapus relasi fasilitas
  mysqli_query($koneksi, "DELETE FROM ruangan_fasilitas WHERE ruangan_id=$id");

  // hapus ruangan
  mysqli_query($koneksi, "DELETE FROM ruangan WHERE id=$id");

  mysqli_commit($koneksi);

  /* =====================
     NOTIFIKASI
  ===================== */
  kirimNotifikasiByRole(
    $koneksi,
    ['admin', 'kepala_bagian'],
    'Ruangan Dihapus',
    "Ruangan \"$nama_ruangan\" telah dihapus oleh admin."
  );

  header("Location: index.php?success=delete");
  exit;
} catch (Exception $e) {
  mysqli_rollback($koneksi);
  die("Gagal hapus data");
}
